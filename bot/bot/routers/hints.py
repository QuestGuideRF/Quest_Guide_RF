import logging
from aiogram import Router, F
from aiogram.types import CallbackQuery, InputFile, FSInputFile
from aiogram.fsm.context import FSMContext
from pathlib import Path
from sqlalchemy.ext.asyncio import AsyncSession
from bot.services.hints import HintService
from bot.repositories.progress import ProgressRepository
from bot.keyboards.user import UserKeyboards
from bot.models.user import User
logger = logging.getLogger(__name__)
router = Router()
@router.callback_query(F.data.startswith("hint:request:"))
async def hint_request(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    try:
        from bot.utils.i18n import i18n
        user_lang = getattr(user, 'language', 'ru') if hasattr(user, 'language') else 'ru'
        point_id = int(callback.data.split(":")[-1])
        from bot.repositories.point import PointRepository
        point_repo = PointRepository(session)
        point = await point_repo.get(point_id)
        if not point:
            await callback.answer("❌ Точка не найдена", show_alert=True)
            return
        progress_repo = ProgressRepository(session)
        progress = await progress_repo.get_active_progress(user.id, point.route_id)
        if not progress:
            await callback.answer("❌ У вас нет активного квеста", show_alert=True)
            return
        hint_service = HintService(session)
        can_use, message, hints_used, max_hints = await hint_service.check_hint_availability(
            user.id, progress.route_id, point_id
        )
        if not can_use:
            await callback.answer(f"❌ {message}", show_alert=True)
            return
        available_hints = await hint_service.get_available_hints(user.id, point_id)
        if not available_hints:
            await callback.answer("❌ Все подсказки уже использованы для этой точки", show_alert=True)
            return
        used_levels = []
        for hint in available_hints:
            is_used = await hint_service.user_hint_repo.is_hint_used(user.id, hint.id)
            if is_used:
                used_levels.append(hint.level)
        available_levels = [hint.level for hint in available_hints if hint.level not in used_levels]
        if len(available_levels) > 1:
            await callback.message.edit_text(
                f"{i18n.get('hint_choose_level_title', user_lang)}\n\n"
                f"{i18n.get('hints_remaining', user_lang, remaining=max_hints - hints_used, max=max_hints)}\n\n"
                f"{i18n.get('hint_level_easy_desc', user_lang)}\n"
                f"{i18n.get('hint_level_medium_desc', user_lang)}\n"
                f"{i18n.get('hint_level_detailed_desc', user_lang)}",
                reply_markup=UserKeyboards.hint_level_selection(point_id, available_levels, used_levels, language=user_lang),
                parse_mode="HTML"
            )
        else:
            next_level = available_levels[0] if available_levels else None
            if next_level:
                from aiogram.types import CallbackQuery as CallbackQueryType
                fake_callback = CallbackQueryType(
                    id=callback.id,
                    from_user=callback.from_user,
                    chat_instance=callback.chat_instance,
                    data=f"hint:show:{point_id}:{next_level}",
                    message=callback.message
                )
                await hint_show(fake_callback, session, user, original_callback=callback)
            else:
                await callback.answer("❌ Нет доступных подсказок", show_alert=True)
    except Exception as e:
        logger.error(f"Error in hint_request: {e}", exc_info=True)
        await callback.answer("❌ Ошибка при получении подсказки", show_alert=True)
@router.callback_query(F.data.startswith("hint:show:"))
async def hint_show(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    original_callback: CallbackQuery = None,
):
    callback_to_answer = original_callback if original_callback else callback
    try:
        from bot.utils.i18n import i18n
        user_lang = getattr(user, 'language', 'ru') if hasattr(user, 'language') else 'ru'
        parts = callback.data.split(":")
        point_id = int(parts[2])
        hint_level = int(parts[3])
        from bot.repositories.point import PointRepository
        point_repo = PointRepository(session)
        point = await point_repo.get(point_id)
        if not point:
            await callback_to_answer.answer("❌ Точка не найдена", show_alert=True)
            return
        progress_repo = ProgressRepository(session)
        progress = await progress_repo.get_active_progress(user.id, point.route_id)
        if not progress:
            await callback_to_answer.answer("❌ У вас нет активного квеста", show_alert=True)
            return
        hint_service = HintService(session)
        hint = await hint_service.use_hint(user.id, progress.route_id, point_id, hint_level)
        if not hint:
            await callback_to_answer.answer("❌ Подсказка недоступна", show_alert=True)
            return
        hint_text = hint.text
        if user.language == 'en' and hint.text_en:
            hint_text = hint.text_en
        level_name_map = {
            1: i18n.get("hint_level_easy_name", user_lang),
            2: i18n.get("hint_level_medium_name", user_lang),
            3: i18n.get("hint_level_detailed_name", user_lang),
        }
        level_name = level_name_map.get(hint.level, str(hint.level))
        message_text = (
            f"{hint.level_emoji} <b>{i18n.get('hint', user_lang)} ({level_name})</b>\n\n"
            f"{hint_text}\n\n"
        )
        stats = await hint_service.get_hints_stats(user.id, progress.route_id)
        message_text += i18n.get("hint_used_stats", user_lang, used=stats['used'], max=stats['max'])
        from aiogram.utils.keyboard import InlineKeyboardBuilder
        from aiogram.types import InlineKeyboardButton
        keyboard_builder = InlineKeyboardBuilder()
        keyboard_builder.row(
            InlineKeyboardButton(
                text=i18n.get("back_to_task", user_lang, default="◀️ Назад к заданию"),
                callback_data=f"hint:back_to_task:{point_id}"
            )
        )
        if hint.image_path:
            try:
                import os
                base_dir = Path(__file__).parent.parent.parent.parent
                image_full_path = base_dir / hint.image_path.lstrip("/")
                if image_full_path.exists():
                    photo = FSInputFile(str(image_full_path))
                    await callback.message.delete()
                    await callback.message.answer_photo(
                        photo=photo,
                        caption=message_text,
                        reply_markup=keyboard_builder.as_markup(),
                        parse_mode="HTML"
                    )
                else:
                    logger.warning(f"Hint image not found: {image_full_path}")
                    await callback.message.edit_text(
                        message_text,
                        reply_markup=keyboard_builder.as_markup(),
                        parse_mode="HTML"
                    )
            except Exception as e:
                logger.error(f"Error sending hint image: {e}", exc_info=True)
                await callback.message.edit_text(
                    message_text,
                    reply_markup=keyboard_builder.as_markup(),
                    parse_mode="HTML"
                )
        else:
            await callback.message.edit_text(
                message_text,
                reply_markup=keyboard_builder.as_markup(),
                parse_mode="HTML"
            )
        if hint.has_map and hint.map_image_path:
            try:
                map_path = Path("..") / ".." / hint.map_image_path.lstrip("/")
                if map_path.exists():
                    photo = FSInputFile(str(map_path))
                    await callback.message.answer_photo(
                        photo=photo,
                        caption=i18n.get("hint_map_caption", user_lang, default="🗺 Мини-карта с местоположением")
                    )
                else:
                    logger.warning(f"Map image not found: {map_path}")
            except Exception as e:
                logger.error(f"Error sending map image: {e}", exc_info=True)
        await callback_to_answer.answer(i18n.get("hint_used", user_lang, default="✅ Подсказка использована"), show_alert=True)
    except Exception as e:
        logger.error(f"Error in hint_show: {e}", exc_info=True)
        callback_to_answer = original_callback if original_callback else callback
        await callback_to_answer.answer("❌ Ошибка при показе подсказки", show_alert=True)
@router.callback_query(F.data == "hint:cancel")
async def hint_cancel(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.repositories.progress import ProgressRepository
    from bot.repositories.point import PointRepository
    from bot.utils.i18n import i18n, get_localized_field
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from bot.services.hints import HintService
    try:
        progress_repo = ProgressRepository(session)
        point_repo = PointRepository(session)
        from sqlalchemy import select
        from bot.models.user_progress import UserProgress, ProgressStatus
        result = await session.execute(
            select(UserProgress).where(
                UserProgress.user_id == user.id,
                UserProgress.status == ProgressStatus.IN_PROGRESS,
            ).order_by(UserProgress.started_at.desc())
        )
        all_progress = result.scalars().all()
        if not all_progress:
            await callback.answer("❌ У вас нет активного квеста", show_alert=True)
            return
        progress = all_progress[0]
        current_point = await point_repo.get_with_images(progress.current_point_id)
        if not current_point:
            await callback.answer("❌ Точка не найдена", show_alert=True)
            return
        point_name = get_localized_field(current_point, 'name', user.language)
        point_task = get_localized_field(current_point, 'task_text', user.language)
        task_text = f"📍 {i18n.get('point', user.language)} {progress.current_point_order + 1}: {point_name}\n\n"
        task_text += f"📝 {i18n.get('task', user.language)}:\n{point_task}\n\n"
        if current_point.task_type in ['text', 'riddle']:
            point_text_answer_hint = get_localized_field(current_point, 'text_answer_hint', user.language)
            task_text += f"\n✍️ {i18n.get('send_answer_text', user.language)}!"
            if point_text_answer_hint:
                task_text += f"\n💡 {i18n.get('hint', user.language)}: {point_text_answer_hint}"
        else:
            if current_point.require_pose:
                pose_names = {
                    "hands_up": i18n.get("pose_hands_up", user.language, default="hands up"),
                    "heart": i18n.get("pose_heart", user.language, default="heart with hands"),
                    "point": i18n.get("pose_point", user.language, default="point with finger"),
                }
                task_text += f"🤸 {i18n.get('pose_required', user.language)}: {pose_names.get(current_point.require_pose, current_point.require_pose)}\n"
            task_text += f"\n{i18n.get('send_photo', user.language)}"
        keyboard_builder = InlineKeyboardBuilder()
        if current_point.audio_enabled:
            audio_buttons = [
                InlineKeyboardButton(
                    text="🎧 Аудиогид (RU)",
                    callback_data=f"audio:play:{current_point.id}:ru",
                )
            ]
            has_en_text = (
                hasattr(current_point, 'audio_text_en') and current_point.audio_text_en or
                hasattr(current_point, 'task_text_en') and current_point.task_text_en or
                hasattr(current_point, 'fact_text_en') and current_point.fact_text_en
            )
            if has_en_text:
                audio_buttons.append(
                    InlineKeyboardButton(
                        text="🎧 Audio Guide (EN)",
                        callback_data=f"audio:play:{current_point.id}:en",
                    )
                )
            keyboard_builder.row(*audio_buttons)
        hint_service = HintService(session)
        can_use, _, hints_used, max_hints = await hint_service.check_hint_availability(
            user.id, progress.route_id, current_point.id
        )
        if can_use:
            hint_button_text = i18n.get("hint", user.language, default="💡 Подсказка")
            if hints_used < max_hints:
                hint_button_text += f" ({max_hints - hints_used} {i18n.get('hints_left', user.language, default='осталось')})"
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=hint_button_text,
                    callback_data=f"hint:request:{current_point.id}",
                )
            )
        keyboard_builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel_quest", user.language),
                callback_data=f"cancel_quest:{progress.route_id}",
            )
        )
        await callback.message.edit_text(
            task_text,
            reply_markup=keyboard_builder.as_markup(),
        )
        await callback.answer()
    except Exception as e:
        logger.error(f"Ошибка при отмене подсказки: {e}", exc_info=True)
        await callback.answer("❌ Ошибка при возврате к заданию", show_alert=True)
@router.callback_query(F.data == "hint:none")
async def hint_none(callback: CallbackQuery):
    await callback.answer(
        "❌ Вы использовали все доступные подсказки на этом маршруте. "
        "Попробуйте найти точку самостоятельно или обратитесь к администратору.",
        show_alert=True
    )
@router.callback_query(F.data.startswith("hint:back_to_task:"))
async def hint_back_to_task(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.repositories.progress import ProgressRepository
    from bot.repositories.point import PointRepository
    from bot.utils.i18n import i18n, get_localized_field
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from bot.services.hints import HintService
    try:
        point_id = int(callback.data.split(":")[-1])
        point_repo = PointRepository(session)
        current_point = await point_repo.get_with_images(point_id)
        if not current_point:
            await callback.answer("❌ Точка не найдена", show_alert=True)
            return
        progress_repo = ProgressRepository(session)
        progress = await progress_repo.get_active_progress(user.id, current_point.route_id)
        if not progress:
            await callback.answer("❌ У вас нет активного квеста", show_alert=True)
            return
        if not current_point:
            await callback.answer("❌ Точка не найдена", show_alert=True)
            return
        point_name = get_localized_field(current_point, 'name', user.language)
        point_task = get_localized_field(current_point, 'task_text', user.language)
        task_text = f"📍 {i18n.get('point', user.language)} {progress.current_point_order + 1}: {point_name}\n\n"
        task_text += f"📝 {i18n.get('task', user.language)}:\n{point_task}\n\n"
        if current_point.task_type in ['text', 'riddle']:
            point_text_answer_hint = get_localized_field(current_point, 'text_answer_hint', user.language)
            task_text += f"\n✍️ {i18n.get('send_answer_text', user.language)}!"
            if point_text_answer_hint:
                task_text += f"\n💡 {i18n.get('hint', user.language)}: {point_text_answer_hint}"
        else:
            if current_point.require_pose:
                pose_names = {
                    "hands_up": i18n.get("pose_hands_up", user.language, default="hands up"),
                    "heart": i18n.get("pose_heart", user.language, default="heart with hands"),
                    "point": i18n.get("pose_point", user.language, default="point with finger"),
                }
                task_text += f"🤸 {i18n.get('pose_required', user.language)}: {pose_names.get(current_point.require_pose, current_point.require_pose)}\n"
            task_text += f"\n{i18n.get('send_photo', user.language)}"
        keyboard_builder = InlineKeyboardBuilder()
        if current_point.audio_enabled:
            audio_buttons = [
                InlineKeyboardButton(
                    text="🎧 Аудиогид (RU)",
                    callback_data=f"audio:play:{current_point.id}:ru",
                )
            ]
            has_en_text = (
                hasattr(current_point, 'audio_text_en') and current_point.audio_text_en or
                hasattr(current_point, 'task_text_en') and current_point.task_text_en or
                hasattr(current_point, 'fact_text_en') and current_point.fact_text_en
            )
            if has_en_text:
                audio_buttons.append(
                    InlineKeyboardButton(
                        text="🎧 Audio Guide (EN)",
                        callback_data=f"audio:play:{current_point.id}:en",
                    )
                )
            keyboard_builder.row(*audio_buttons)
        hint_service = HintService(session)
        can_use, _, hints_used, max_hints = await hint_service.check_hint_availability(
            user.id, progress.route_id, current_point.id
        )
        if can_use:
            hint_button_text = i18n.get("hint", user.language, default="💡 Подсказка")
            if hints_used < max_hints:
                hint_button_text += f" ({max_hints - hints_used} {i18n.get('hints_left', user.language, default='осталось')})"
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=hint_button_text,
                    callback_data=f"hint:request:{current_point.id}",
                )
            )
        keyboard_builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel_quest", user.language),
                callback_data=f"cancel_quest:{progress.route_id}",
            )
        )
        await callback.message.edit_text(
            task_text,
            reply_markup=keyboard_builder.as_markup(),
        )
        await callback.answer()
    except Exception as e:
        logger.error(f"Ошибка при возврате к заданию: {e}", exc_info=True)
        await callback.answer("❌ Ошибка при возврате к заданию", show_alert=True)