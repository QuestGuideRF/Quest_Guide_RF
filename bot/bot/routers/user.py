import os
import gc
import logging
import time
from datetime import datetime
from aiogram import Router, F
from aiogram.filters import Command, StateFilter
from aiogram.fsm.context import FSMContext
from aiogram.types import Message, CallbackQuery
from sqlalchemy.ext.asyncio import AsyncSession
from bot.loader import config
from bot.fsm.states import UserStates
from bot.keyboards.user import UserKeyboards
from bot.loader import bot
from bot.models.user import User
from bot.repositories.city import CityRepository
from bot.repositories.route import RouteRepository
from bot.repositories.point import PointRepository
from bot.repositories.progress import ProgressRepository
from bot.repositories.payment import PaymentRepository
from bot.services.vision import VisionService
from bot.services.pose_movenet import PoseService
from bot.services.antifraud import AntiFraudService
from bot.services.admin_notifier import AdminNotifier
from bot.utils.helpers import download_photo, download_photo_by_file_id, format_duration, format_distance, get_point_tasks, tasks_from_models, parse_task_text, split_long_message
from bot.utils.i18n import get_localized_field
from bot.utils.commands import set_user_commands
from bot.repositories.task import TaskRepository
from sqlalchemy import text
from aiogram.types import FSInputFile
async def _check_and_notify_achievements(session: AsyncSession, user: User, message: Message) -> None:
    try:
        from bot.services.achievements import AchievementService
        from bot.utils.i18n import i18n
        svc = AchievementService(session)
        new_achievements = await svc.check_and_grant_achievements(user.id)
        if new_achievements:
            await session.commit()
            lang = user.language
            for ach in new_achievements:
                text_msg = f"🏆 {i18n.get('achievement_unlocked', lang, default='Новое достижение')}: {ach.icon} {ach.name}\n{ach.description}"
                await message.answer(text_msg, parse_mode=None)
    except (ImportError, ModuleNotFoundError):
        pass
    except Exception as e:
        logger.warning(f"[USER {user.telegram_id}] Ошибка проверки достижений: {e}", exc_info=True)
async def _on_quest_completed(session: AsyncSession, user: User, message: Message) -> None:
    await set_user_commands(bot, user.telegram_id, user.language, in_quest=False)
    await _check_and_notify_achievements(session, user, message)
async def _send_post_quest_flow(message: Message, user: User, progress_id: int, completion_msg: str) -> None:
    await message.answer(
        completion_msg,
        reply_markup=UserKeyboards.post_quest_first(progress_id, user.language),
        parse_mode="HTML",
    )
logger = logging.getLogger(__name__)
router = Router()
@router.message(F.sticker | F.animation)
async def reject_stickers_and_gifs(message: Message, user: User):
    from bot.utils.i18n import i18n
    await message.answer(
        i18n.get(
            "stickers_not_supported",
            user.language,
            default="❌ Стикеры и GIF не подходят. Отебись😎 Я как будда мне похуй на твои картинки",
        )
    )
@router.message(Command("promo"))
async def cmd_promo(
    message: Message,
    user: User,
    state: FSMContext,
    session: AsyncSession,
):
    from bot.utils.i18n import i18n
    await state.clear()
    await message.answer(
        i18n.get("promo_menu_title", user.language),
        reply_markup=UserKeyboards.promo_menu(user.language),
        parse_mode="HTML",
    )
@router.callback_query(F.data == "promo:menu")
async def cb_promo_menu(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.clear()
    try:
        await callback.message.edit_text(
            i18n.get("promo_menu_title", user.language),
            reply_markup=UserKeyboards.promo_menu(user.language),
            parse_mode="HTML",
        )
    except Exception:
        await callback.message.answer(
            i18n.get("promo_menu_title", user.language),
            reply_markup=UserKeyboards.promo_menu(user.language),
            parse_mode="HTML",
        )
    await callback.answer()
@router.callback_query(F.data == "promo:enter")
async def cb_promo_enter(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.update_data(route_id=None)
    await state.set_state(UserStates.waiting_promo_code)
    await callback.message.answer(
        i18n.get("promo_code_enter_command", user.language),
        reply_markup=UserKeyboards.cancel_keyboard(user.language),
    )
    await callback.answer()
@router.callback_query(F.data == "promo:my_list")
async def cb_promo_my_list(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    from bot.utils.i18n import i18n, get_localized_field
    from bot.repositories.promo_code import PromoCodeRepository
    from bot.repositories.route import RouteRepository
    from bot.repositories.payment import PaymentRepository
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    promo_repo = PromoCodeRepository(session)
    route_repo = RouteRepository(session)
    payment_repo = PaymentRepository(session)
    uses = await promo_repo.get_uses_by_user(user.id)
    msg_text = i18n.get("promo_my_activations_title", user.language)
    builder = InlineKeyboardBuilder()
    if not uses:
        msg_text += "\n\n" + i18n.get("promo_no_activations", user.language)
    else:
        for use in uses[:10]:
            route_name = ""
            route_id = use.route_id
            if route_id:
                route = await route_repo.get(route_id)
                if route:
                    route_name = get_localized_field(route, "name", user.language) or route.name
            code_str = getattr(use.promo_code, "code", None) or ""
            date_str = use.used_at.strftime("%d.%m.%Y") if use.used_at else ""
            msg_text += f"\n• {i18n.get('promo_use_route', user.language).format(name=route_name or '?', code=code_str)} ({i18n.get('promo_use_at', user.language).format(date=date_str)})"
            if route_id:
                has_paid = await payment_repo.has_paid_for_route(user.id, route_id)
                if has_paid:
                    builder.row(InlineKeyboardButton(
                        text=f"▶️ {route_name}",
                        callback_data=f"start_quest:{route_id}",
                    ))
                else:
                    builder.row(InlineKeyboardButton(
                        text=f"📍 {route_name}",
                        callback_data=f"route:{route_id}",
                    ))
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("promo_enter_code_btn", user.language, default="✏️ Ввести промокод"),
            callback_data="promo:enter",
        )
    )
    builder.row(
        InlineKeyboardButton(text=i18n.get("back", user.language), callback_data="promo:menu"),
    )
    try:
        await callback.message.edit_text(
            msg_text,
            reply_markup=builder.as_markup(),
            parse_mode="HTML",
        )
    except Exception:
        await callback.message.answer(
            msg_text,
            reply_markup=builder.as_markup(),
            parse_mode="HTML",
        )
    await callback.answer()
@router.message(Command("start"))
async def cmd_start(
    message: Message,
    user: User,
    state: FSMContext,
    session: AsyncSession,
):
    from bot.utils.i18n import i18n
    from bot.repositories.user import UserRepository
    await state.clear()
    if message.text:
        parts = message.text.strip().split()
        if len(parts) >= 2 and parts[1].startswith("ref_"):
            try:
                ref_telegram_id = int(parts[1][4:])
                user_repo = UserRepository(session)
                referrer = await user_repo.get_by_telegram_id(ref_telegram_id)
                if (
                    referrer
                    and referrer.id != user.id
                    and getattr(user, "referred_by_id", None) is None
                ):
                    user.referred_by_id = referrer.id
                    await session.commit()
                    await session.refresh(user)
            except (ValueError, IndexError):
                pass
    if not user.language:
        result = await session.execute(
            text("SELECT COUNT(*) FROM user_progress WHERE user_id = :user_id"),
            {"user_id": user.id}
        )
        has_progress = result.scalar() > 0
        if not has_progress:
            await message.answer(
                "👋 Welcome! / Привет!\n\n"
                "Please select your language / Выберите язык:",
                reply_markup=UserKeyboards.language_selection(),
            )
            await state.set_state(UserStates.selecting_language)
            return
    if not user.language:
        user.language = "ru"
        await session.commit()
        await session.refresh(user)
    await message.answer(
        i18n.get("welcome", user.language, name=user.first_name),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
@router.callback_query(F.data == "back_to_main")
async def back_to_main(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.clear()
    await callback.message.edit_text(
        i18n.get("main_menu", user.language),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
    await callback.answer()
@router.callback_query(F.data == "from_review:main_menu")
async def from_review_main_menu(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.clear()
    await callback.message.answer(
        i18n.get("main_menu", user.language),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
    await callback.answer()
@router.callback_query(F.data == "from_review:web")
async def from_review_web(callback: CallbackQuery, session: AsyncSession, user: User):
    from bot.routers.web_auth import _send_web_access
    await _send_web_access(callback.message, session, user, callback.from_user.id)
    await callback.answer()
@router.callback_query(F.data.startswith("post_quest:skip_to_review:"))
async def post_quest_skip_to_review(callback: CallbackQuery, user: User):
    from bot.utils.i18n import i18n
    progress_id = int(callback.data.split(":")[-1])
    text = (
        i18n.get("post_quest_then_review", user.language)
        + "\n\n"
        + i18n.get("can_skip_cmd", user.language)
    )
    await callback.message.edit_text(
        text,
        reply_markup=UserKeyboards.post_quest_review_prompt(progress_id, user.language),
        parse_mode="HTML",
    )
    await callback.answer()
@router.callback_query(F.data.startswith("post_quest:skip_to_quiz:"))
async def post_quest_skip_to_quiz(callback: CallbackQuery, user: User):
    from bot.utils.i18n import i18n
    progress_id = int(callback.data.split(":")[-1])
    text = (
        i18n.get("post_quest_then_quiz", user.language)
        + "\n\n"
        + i18n.get("can_skip_cmd", user.language)
    )
    await callback.message.edit_text(
        text,
        reply_markup=UserKeyboards.post_quest_quiz_prompt(progress_id, user.language),
        parse_mode="HTML",
    )
    await callback.answer()
@router.callback_query(F.data.startswith("post_quest:final:"))
async def post_quest_final(callback: CallbackQuery, user: User):
    from bot.utils.i18n import i18n
    text = i18n.get("thanks", user.language) + "\n\n" + i18n.get("view_certificate", user.language) + " / " + i18n.get("back_to_main", user.language)
    await callback.message.edit_text(
        text,
        reply_markup=UserKeyboards.post_quest_final(user.language),
        parse_mode="HTML",
    )
    await callback.answer()
async def _show_partner_screen(target, user: User, session: AsyncSession, edit: bool = False):
    from bot.utils.i18n import i18n
    from bot.loader import bot
    from bot.repositories.user import UserRepository
    from bot.repositories.token import TokenRepository
    from bot.repositories.payment import PaymentRepository
    from bot.services.referral_service import ReferralService
    bot_me = await bot.get_me()
    username = bot_me.username if bot_me else "bot"
    link = f"https://t.me/{username}?start=ref_{user.telegram_id}"
    ref_service = ReferralService(session)
    ref_info = await ref_service.get_user_referral_info(user.id)
    stats = ref_info.get("stats", {})
    next_level = ref_info.get("next_level")
    levels = ref_info.get("levels", [])
    current_level = stats.get("level", 0)
    paid_referrals = stats.get("paid_referrals", 0)
    total_earnings = stats.get("total_earnings", 0)
    is_partner = stats.get("is_partner", False)
    level_names = {
        0: "Новичок 🌱",
        1: "Начало пути 🌱",
        2: "Активный участник 🔥",
        3: "Главный фанат 🏆",
        4: "Официальный партнёр 👑"
    }
    level_name = level_names.get(current_level, f"Уровень {current_level}")
    text = f"🤝 <b>Партнёрская программа</b>\n\n"
    text += f"🎖 Ваш уровень: <b>{level_name}</b>\n"
    text += f"👥 Приглашённых с покупками: <b>{paid_referrals}</b>\n"
    text += f"💰 Всего заработано: <b>{int(total_earnings)} грошей</b>\n"
    if is_partner:
        text += f"⭐ <b>Вы официальный партнёр!</b>\n"
    text += f"\n📎 Ваша реферальная ссылка:\n<code>{link}</code>\n"
    if next_level:
        remaining = next_level.get("remaining", 0)
        next_name = next_level.get("name", "Следующий уровень")
        next_icon = next_level.get("icon", "🎯")
        text += f"\n📊 <b>До уровня «{next_name}» {next_icon}:</b>\n"
        text += f"   {paid_referrals}/{next_level.get('required_referrals', 0)} приглашённых (осталось {remaining})\n"
        progress_bar = _make_progress_bar(paid_referrals, next_level.get('required_referrals', 1))
        text += f"   {progress_bar}\n"
    keyboard = UserKeyboards.partner_menu(user.language)
    if edit:
        try:
            await target.edit_text(text, reply_markup=keyboard, parse_mode="HTML")
        except Exception:
            await target.answer(text, reply_markup=keyboard, parse_mode="HTML")
    else:
        await target.answer(text, reply_markup=keyboard, parse_mode="HTML")
def _make_progress_bar(current: int, total: int, length: int = 10) -> str:
    if total <= 0:
        return "▓" * length
    filled = min(int((current / total) * length), length)
    empty = length - filled
    return "▓" * filled + "░" * empty
@router.message(Command("partner"))
async def cmd_partner(message: Message, user: User, session: AsyncSession, state: FSMContext):
    await state.clear()
    await _show_partner_screen(message, user, session, edit=False)
@router.callback_query(F.data == "open_partner")
async def cb_open_partner(callback: CallbackQuery, user: User, session: AsyncSession, state: FSMContext):
    await state.clear()
    await _show_partner_screen(callback.message, user, session, edit=True)
    await callback.answer()
@router.callback_query(F.data == "partner:levels_info")
async def cb_partner_levels_info(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.services.referral_service import ReferralService
    ref_service = ReferralService(session)
    ref_info = await ref_service.get_user_referral_info(user.id)
    levels = ref_info.get("levels", [])
    lang = user.language
    title = i18n.get("partner_levels_title", lang, default="🏆 Уровни партнёрки:")
    friends_word = i18n.get("partner_friends_word", lang, default="друзей")
    level_prefix = i18n.get("partner_level_prefix", lang, default="Ур.")
    text = f"{title}\n\n"
    for lvl in levels:
        name = (lvl.name_en if lang == "en" and getattr(lvl, "name_en", None) else None) or lvl.name
        desc = (lvl.description_en if lang == "en" and getattr(lvl, "description_en", None) else None) or lvl.description or ""
        text += f"<b>{level_prefix}{lvl.level}: {name}</b> ({lvl.required_referrals} {friends_word})\n"
        if desc:
            text += f"   → {desc}\n"
        text += "\n"
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    builder = InlineKeyboardBuilder()
    builder.row(
        InlineKeyboardButton(text=i18n.get("back", lang), callback_data="open_partner"),
    )
    await callback.message.answer(text.strip(), reply_markup=builder.as_markup(), parse_mode="HTML")
    await callback.answer()
@router.message(Command("commands"))
@router.message(Command("comand"))
async def cmd_commands(message: Message, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.clear()
    await message.answer(
        i18n.get("commands_menu_title", user.language),
        reply_markup=UserKeyboards.commands_menu(user.language),
        parse_mode="HTML",
    )
@router.callback_query(F.data == "enter_promo")
async def cb_enter_promo(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.clear()
    try:
        await callback.message.edit_text(
            i18n.get("promo_menu_title", user.language),
            reply_markup=UserKeyboards.promo_menu(user.language),
            parse_mode="HTML",
        )
    except Exception:
        await callback.message.answer(
            i18n.get("promo_menu_title", user.language),
            reply_markup=UserKeyboards.promo_menu(user.language),
            parse_mode="HTML",
        )
    await callback.answer()
@router.callback_query(F.data == "show_top")
async def cb_show_top(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n, get_localized_field
    route_repo = RouteRepository(session)
    top_routes = await route_repo.get_top_routes(limit=10)
    if not top_routes:
        try:
            await callback.message.edit_text(
                f"📈 <b>{i18n.get('top_routes_title', user.language)}</b>\n\n"
                f"{i18n.get('top_routes_empty', user.language)}",
                parse_mode="HTML",
            )
        except Exception:
            await callback.message.answer(
                f"📈 <b>{i18n.get('top_routes_title', user.language)}</b>\n\n"
                f"{i18n.get('top_routes_empty', user.language)}",
                parse_mode="HTML",
            )
        await callback.answer()
        return
    text = f"📈 <b>{i18n.get('top_routes_list', user.language)}</b>\n\n"
    text += f"{i18n.get('top_routes_popular', user.language)}\n\n"
    for i, (route, completions, avg_time) in enumerate(top_routes, 1):
        medal = {1: "🥇", 2: "🥈", 3: "🥉"}.get(i, f"{i}.")
        route_name = get_localized_field(route, 'name', user.language)
        text += f"{medal} <b>{route_name}</b>\n"
        text += f"   👥 {i18n.get('top_completions', user.language)}: {completions}\n"
        if avg_time > 0:
            text += f"   ⏱ {i18n.get('top_avg_time', user.language)}: {format_duration(avg_time)}\n"
        text += f"   💰 {i18n.get('top_price', user.language)}: {route.price} грошей\n"
        text += f"   🌐 <a href='{config.web.site_url}/routes/view.php?id={route.id}'>{i18n.get('top_more', user.language)}</a>\n\n"
    try:
        await callback.message.edit_text(text, parse_mode="HTML", disable_web_page_preview=True)
    except Exception:
        await callback.message.answer(text, parse_mode="HTML", disable_web_page_preview=True)
    await callback.answer()
@router.callback_query(F.data == "select_city")
async def select_city(
    callback: CallbackQuery,
    user: User,
    session: AsyncSession,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    city_repo = CityRepository(session)
    cities = await city_repo.get_active()
    if not cities:
        await callback.answer(i18n.get("no_cities", user.language), show_alert=True)
        return
    await state.set_state(UserStates.selecting_city)
    await callback.message.edit_text(
        i18n.get("choose_city", user.language),
        reply_markup=UserKeyboards.city_list(cities, user.language),
    )
    await callback.answer()
@router.callback_query(F.data.startswith("city:"))
async def city_selected(
    callback: CallbackQuery,
    user: User,
    session: AsyncSession,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    city_id = int(callback.data.split(":")[1])
    route_repo = RouteRepository(session)
    routes = await route_repo.get_by_city(city_id, active_only=True)
    if not routes:
        await callback.answer(i18n.get("no_routes", user.language), show_alert=True)
        return
    await state.update_data(city_id=city_id)
    await state.set_state(UserStates.selecting_route)
    await callback.message.edit_text(
        i18n.get("choose_route", user.language),
        reply_markup=UserKeyboards.route_list(routes, city_id=city_id, show_filter_button=True, language=user.language),
    )
    await callback.answer()
@router.callback_query(F.data == "back_to_routes")
async def back_to_routes(
    callback: CallbackQuery,
    user: User,
    session: AsyncSession,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    city_id = data.get("city_id")
    if not city_id:
        await select_city(callback, user, session, state)
        return
    route_repo = RouteRepository(session)
    routes = await route_repo.get_by_city(city_id, active_only=True)
    await state.set_state(UserStates.selecting_route)
    await callback.message.edit_text(
        i18n.get("choose_route", user.language),
        reply_markup=UserKeyboards.route_list(routes, city_id=city_id, show_filter_button=True, language=user.language),
    )
    await callback.answer()
@router.callback_query(F.data.startswith("route:"))
async def route_selected(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[1])
    route_repo = RouteRepository(session)
    payment_repo = PaymentRepository(session)
    from bot.utils.i18n import i18n, get_localized_field
    route, tags = await route_repo.get_route_with_tags(route_id)
    if not route:
        await callback.answer(i18n.get("route_not_found", user.language), show_alert=True)
        return
    has_paid = await payment_repo.has_paid_for_route(user.id, route_id)
    avg_time = await route_repo.get_average_completion_time(route_id)
    route_name = get_localized_field(route, 'name', user.language)
    route_description = get_localized_field(route, 'description', user.language)
    description = f"📍 <b>{route_name}</b>\n\n"
    if route_description:
        description += f"{route_description}\n\n"
    description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
    description += f"• {i18n.get('points', user.language)}: {len(route.points)}\n"
    if route.estimated_duration:
        description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
    if avg_time:
        description += f"• {i18n.get('average_time_users', user.language)}: ~{format_duration(avg_time)}\n"
    if route.distance:
        description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
    if route.difficulty:
        difficulty_names = {
            1: i18n.get('easy', user.language),
            2: i18n.get('medium', user.language),
            3: i18n.get('hard', user.language)
        }
        description += f"• {i18n.get('difficulty', user.language)}: {difficulty_names.get(route.difficulty, i18n.get('medium', user.language))}\n"
    description += f"• {i18n.get('price', user.language)}: {route.price} грошей\n"
    if tags:
        tag_texts = []
        for tag in tags:
            tag_name = get_localized_field(tag, 'name', user.language)
            tag_texts.append(f"{tag.icon} {tag_name}")
        description += f"\n🏷 <b>{i18n.get('tags', user.language)}:</b> {', '.join(tag_texts)}\n"
    description += f"\n🌐 <a href='{config.web.site_url}/routes/view.php?id={route_id}'>{i18n.get('more_on_site', user.language)}</a>\n"
    if has_paid:
        description += i18n.get("route_paid", user.language)
    else:
        description += i18n.get("route_need_payment", user.language)
    await state.update_data(route_id=route_id)
    await callback.message.edit_text(
        description,
        reply_markup=UserKeyboards.route_detail(route_id, has_paid, user.language, show_promo=not has_paid),
        parse_mode="HTML",
        disable_web_page_preview=True,
    )
    await callback.answer()
@router.callback_query(F.data.startswith("start_quest:"))
async def start_quest(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    from bot.utils.settings import is_subscription_check_enabled
    from bot.loader import bot
    from aiogram.exceptions import TelegramBadRequest
    from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
    subscription_check_enabled = await is_subscription_check_enabled(session)
    if subscription_check_enabled:
        if (config.channel.channel_id and config.channel.channel_id != 0) or config.channel.channel_username:
            try:
                user_id = user.telegram_id if hasattr(user, 'telegram_id') else user.id
                member = None
                if config.channel.channel_id and config.channel.channel_id != 0:
                    try:
                        channel_id = config.channel.channel_id
                        if channel_id > 0:
                            channel_id = -1000000000000 - channel_id
                        member = await bot.get_chat_member(
                            chat_id=channel_id,
                            user_id=user_id
                        )
                    except TelegramBadRequest as e:
                        error_msg = str(e).lower()
                        if "member list is inaccessible" in error_msg or "chat not found" in error_msg:
                            if config.channel.channel_username:
                                try:
                                    member = await bot.get_chat_member(
                                        chat_id=f"@{config.channel.channel_username}",
                                        user_id=user_id
                                    )
                                except TelegramBadRequest:
                                    pass
                elif config.channel.channel_username:
                    try:
                        member = await bot.get_chat_member(
                            chat_id=f"@{config.channel.channel_username}",
                            user_id=user_id
                        )
                    except TelegramBadRequest:
                        pass
                if member:
                    status_val = getattr(member.status, 'value', str(member.status)).lower() if member.status else ''
                    is_subscribed = status_val in ('member', 'administrator', 'creator')
                    if not is_subscribed:
                        channel_username = config.channel.channel_username or "questguiderf"
                        channel_link = f"https://t.me/{channel_username}"
                        keyboard = InlineKeyboardMarkup(inline_keyboard=[
                            [InlineKeyboardButton(
                                text=i18n.get("channel_button", user.language, default="📢 Перейти в канал"),
                                url=channel_link
                            )],
                            [InlineKeyboardButton(
                                text=i18n.get("subscribe_button", user.language, default="✅ Я подписался"),
                                callback_data="check_subscription"
                            )]
                        ])
                        subscribe_text = i18n.get("subscribe_required", user.language)
                        await callback.message.edit_text(
                            subscribe_text,
                            reply_markup=keyboard,
                            parse_mode="HTML"
                        )
                        await callback.answer(
                            i18n.get("subscribe_fail", user.language, default="❌ Вы не подписаны на канал"),
                            show_alert=True
                        )
                        return
            except Exception as e:
                logger.error(f"Ошибка проверки подписки при начале квеста: {e}")
    route_id = int(callback.data.split(":")[1])
    route_repo = RouteRepository(session)
    point_repo = PointRepository(session)
    progress_repo = ProgressRepository(session)
    progress = await progress_repo.get_active_progress(user.id, route_id)
    if not progress:
        route = await route_repo.get_with_points(route_id)
        if not route or not route.points:
            await callback.answer(i18n.get("route_empty", user.language), show_alert=True)
            return
        first_point = route.points[0]
        progress = await progress_repo.start_route(
            user_id=user.id,
            route_id=route_id,
            first_point_id=first_point.id,
        )
    current_point = await point_repo.get_with_tasks(progress.current_point_id)
    if not current_point:
        route = await route_repo.get_with_points(route_id)
        if not route or not route.points:
            await callback.answer(i18n.get("route_empty", user.language), show_alert=True)
            return
        current_point = route.points[0]
        progress.current_point_id = current_point.id
        progress.current_point_order = current_point.order
        await session.commit()
        await session.refresh(progress)
        current_point = await point_repo.get_with_tasks(current_point.id)
    tasks = get_point_tasks(current_point)
    if not tasks:
        task_repo = TaskRepository(session)
        task_models = await task_repo.get_by_point(current_point.id)
        tasks = tasks_from_models(task_models)
    if not tasks:
        await callback.answer("❌ Ошибка: у точки нет заданий", show_alert=True)
        return
    current_task_index = 0
    current_task = tasks[current_task_index]
    await state.set_state(UserStates.in_quest)
    await state.update_data(
        route_id=route_id,
        progress_id=progress.id,
        point_id=current_point.id,
        task_index=current_task_index,
        total_tasks=len(tasks),
    )
    await set_user_commands(bot, callback.from_user.id, user.language, in_quest=True)
    point_name = get_localized_field(current_point, 'name', user.language)
    task_text_value = current_task.get('task_text_en') if user.language == 'en' and current_task.get('task_text_en') else current_task.get('task_text', '')
    parsed = parse_task_text(task_text_value)
    header = f"{progress.current_point_order + 1}. {point_name}\n\n"
    messages_to_send = []
    if parsed['directions']:
        directions_msg = header + f"{parsed['directions']}"
        messages_to_send.append(directions_msg)
        header = ""
    audio_text_value = get_localized_field(current_point, 'audio_text', user.language)
    if audio_text_value:
        audio_msg = (header + audio_text_value) if header else audio_text_value
        messages_to_send.append(audio_msg)
        header = ""
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from bot.utils.helpers import yandex_maps_url
    keyboard_builder = InlineKeyboardBuilder()
    if getattr(user, "show_map", False) and getattr(current_point, "latitude", None) is not None and getattr(current_point, "longitude", None) is not None:
        keyboard_builder.row(
            InlineKeyboardButton(
                text=i18n.get("yandex_maps_button", user.language),
                url=yandex_maps_url(current_point.latitude, current_point.longitude),
            )
        )
    keyboard_builder.row(
        InlineKeyboardButton(
            text=i18n.get("i_am_here", user.language),
            callback_data=f"i_am_here:{current_point.id}:{current_task_index}",
        )
    )
    keyboard_builder.row(
        InlineKeyboardButton(
            text=i18n.get("cancel_quest", user.language),
            callback_data=f"cancel_quest:{route_id}",
        )
    )
    await state.update_data(
        route_id=route_id,
        progress_id=progress.id,
        point_id=current_point.id,
        task_index=current_task_index,
        total_tasks=len(tasks),
        waiting_for_arrival=True,
    )
    for i, msg_text in enumerate(messages_to_send):
        msg_parts = split_long_message(msg_text)
        is_last = (i == len(messages_to_send) - 1)
        for j, part in enumerate(msg_parts):
            is_last_part = (j == len(msg_parts) - 1)
            if i == 0 and j == 0:
                await callback.message.edit_text(
                    part,
                    reply_markup=keyboard_builder.as_markup() if is_last and is_last_part else None,
                )
            elif is_last and is_last_part:
                await callback.message.answer(part, reply_markup=keyboard_builder.as_markup())
            else:
                await callback.message.answer(part)
    await callback.answer()
@router.callback_query(F.data.startswith("i_am_here:"))
async def i_am_here_handler(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n, get_localized_field
    from bot.repositories.point import PointRepository
    from bot.repositories.progress import ProgressRepository
    from bot.utils.helpers import get_point_tasks, split_long_message
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    parts = callback.data.split(":")
    point_id = int(parts[1])
    task_index = int(parts[2])
    data = await state.get_data()
    route_id = data.get("route_id")
    progress_id = data.get("progress_id")
    point_repo = PointRepository(session)
    progress_repo = ProgressRepository(session)
    point = await point_repo.get_with_tasks(point_id)
    if not point:
        await callback.answer("Точка не найдена", show_alert=True)
        return
    progress = await progress_repo.get(progress_id)
    if not progress:
        await callback.answer("Прогресс не найден", show_alert=True)
        return
    tasks = get_point_tasks(point)
    if not tasks:
        task_repo = TaskRepository(session)
        task_models = await task_repo.get_by_point(point.id)
        tasks = tasks_from_models(task_models)
    if not tasks or task_index >= len(tasks):
        await callback.answer("Задание не найдено", show_alert=True)
        return
    point_fact = get_localized_field(point, 'fact_text', user.language)
    fact_msg = f"{point_fact}" if point_fact else None
    keyboard_builder = InlineKeyboardBuilder()
    has_audio_ru = bool(getattr(point, "audio_text", None))
    has_audio_en = bool(getattr(point, "audio_text_en", None))
    if point.audio_enabled or has_audio_ru or has_audio_en:
        audio_buttons = []
        if has_audio_ru or point.audio_enabled:
            audio_buttons.append(
                InlineKeyboardButton(
                    text="🎧 Аудиогид (RU)",
                    callback_data=f"audio:play:{point.id}:ru",
                )
            )
        if has_audio_en:
            audio_buttons.append(
                InlineKeyboardButton(
                    text="🎧 Audio Guide (EN)",
                    callback_data=f"audio:play:{point.id}:en",
                )
            )
        if audio_buttons:
            keyboard_builder.row(*audio_buttons)
    keyboard_builder.row(
        InlineKeyboardButton(
            text=i18n.get("proceed_to_task", user.language),
            callback_data=f"proceed_to_task:{point.id}:{task_index}",
        )
    )
    keyboard_builder.row(
        InlineKeyboardButton(
            text=i18n.get("cancel_quest", user.language),
            callback_data=f"cancel_quest:{route_id}",
        )
    )
    await state.update_data(
        waiting_for_arrival=False,
        waiting_for_fact=True,
    )
    if fact_msg:
        msg_parts = split_long_message(fact_msg)
        for j, part in enumerate(msg_parts):
            is_last_part = (j == len(msg_parts) - 1)
            if is_last_part:
                await callback.message.answer(part, reply_markup=keyboard_builder.as_markup())
            else:
                await callback.message.answer(part)
    else:
        await callback.message.answer(
            i18n.get("proceed_to_task", user.language),
            reply_markup=keyboard_builder.as_markup()
        )
    await callback.answer()
@router.callback_query(F.data.startswith("proceed_to_task:"))
async def proceed_to_task_handler(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n, get_localized_field
    from bot.repositories.point import PointRepository
    from bot.repositories.progress import ProgressRepository
    from bot.utils.helpers import get_point_tasks, parse_task_text, split_long_message
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    parts = callback.data.split(":")
    point_id = int(parts[1])
    task_index = int(parts[2])
    data = await state.get_data()
    route_id = data.get("route_id")
    progress_id = data.get("progress_id")
    point_repo = PointRepository(session)
    progress_repo = ProgressRepository(session)
    point = await point_repo.get_with_tasks(point_id)
    if not point:
        await callback.answer("Точка не найдена", show_alert=True)
        return
    progress = await progress_repo.get(progress_id)
    if not progress:
        await callback.answer("Прогресс не найден", show_alert=True)
        return
    tasks = get_point_tasks(point)
    if not tasks:
        task_repo = TaskRepository(session)
        task_models = await task_repo.get_by_point(point.id)
        tasks = tasks_from_models(task_models)
    if not tasks or task_index >= len(tasks):
        await callback.answer("Задание не найдено", show_alert=True)
        return
    current_task = tasks[task_index]
    task_text_value = current_task.get('task_text_en') if user.language == 'en' and current_task.get('task_text_en') else current_task.get('task_text', '')
    parsed = parse_task_text(task_text_value)
    task_header = f"{task_index + 1}/{len(tasks)}\n" if len(tasks) > 1 else ""
    task_msg = task_header + parsed['task']
    if current_task.get('task_type') in ['text', 'riddle']:
        task_msg += f"\n\n✍️ {i18n.get('send_answer_text', user.language)}!"
        task_hint = current_task.get('text_answer_hint')
        if task_hint:
            task_msg += f"\n💡 {i18n.get('hint', user.language)}: {task_hint}"
        await state.set_state(UserStates.waiting_text_answer)
        await state.update_data(
            route_id=route_id,
            progress_id=progress.id,
            point_id=point.id,
            task_index=task_index,
            total_tasks=len(tasks),
            attempts=0,
            max_attempts=current_task.get('max_attempts', 3),
            current_task_id=current_task.get('id'),
            waiting_for_arrival=False,
            waiting_for_fact=False,
        )
    else:
        task_msg += f"\n\n{i18n.get('send_photo', user.language)}"
        await state.set_state(UserStates.in_quest)
        await state.update_data(
            current_task_id=current_task.get('id'),
            waiting_for_arrival=False,
            waiting_for_fact=False,
        )
    keyboard_builder = InlineKeyboardBuilder()
    keyboard_builder.row(
        InlineKeyboardButton(
            text=i18n.get("cancel_quest", user.language),
            callback_data=f"cancel_quest:{route_id}",
        )
    )
    messages_to_send = [task_msg]
    if parsed['hint'] and current_task.get('task_type') not in ['text', 'riddle']:
        hint_msg = f"💡 {i18n.get('hint', user.language)}:\n{parsed['hint']}"
        messages_to_send.append(hint_msg)
    for i, msg_text in enumerate(messages_to_send):
        msg_parts = split_long_message(msg_text)
        is_last = (i == len(messages_to_send) - 1)
        for j, part in enumerate(msg_parts):
            is_last_part = (j == len(msg_parts) - 1)
            if is_last and is_last_part:
                await callback.message.answer(part, reply_markup=keyboard_builder.as_markup())
            else:
                await callback.message.answer(part)
    await callback.answer()
@router.message(StateFilter(UserStates.in_quest), F.photo)
async def process_quest_photo_preview(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    waiting_for_arrival = data.get("waiting_for_arrival", False)
    waiting_for_fact = data.get("waiting_for_fact", False)
    if waiting_for_arrival or waiting_for_fact:
        from bot.utils.i18n import i18n
        if waiting_for_arrival:
            await message.answer(i18n.get("i_am_here", user.language, default="✅ Сначала нажмите кнопку 'Я на месте'"))
        else:
            await message.answer(i18n.get("proceed_to_task", user.language, default="▶️ Сначала нажмите кнопку 'Приступить к заданию'"))
        return
    from bot.utils.i18n import i18n
    photo = message.photo[-1]
    await state.update_data(pending_photo_file_id=photo.file_id)
    await state.set_state(UserStates.confirming_photo)
    await message.answer_photo(
        photo=photo.file_id,
        caption=i18n.get("photo_confirm_prompt", user.language),
        reply_markup=UserKeyboards.photo_confirm(user.language),
    )
@router.callback_query(F.data == "photo_confirm:retake", StateFilter(UserStates.confirming_photo))
async def cb_photo_retake(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await state.set_state(UserStates.in_quest)
    await state.update_data(pending_photo_file_id=None)
    await callback.message.edit_caption(
        caption=i18n.get("photo_retake_prompt", user.language),
        reply_markup=None,
    )
    await callback.answer()
@router.message(StateFilter(UserStates.confirming_photo), F.photo)
async def process_new_photo_while_confirming(
    message: Message, session: AsyncSession, user: User, state: FSMContext,
):
    from bot.utils.i18n import i18n
    photo = message.photo[-1]
    await state.update_data(pending_photo_file_id=photo.file_id)
    await message.answer_photo(
        photo=photo.file_id,
        caption=i18n.get("photo_confirm_prompt", user.language),
        reply_markup=UserKeyboards.photo_confirm(user.language),
    )
@router.callback_query(F.data == "photo_confirm:yes", StateFilter(UserStates.confirming_photo))
async def cb_photo_confirm(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n, get_localized_field
    from bot.utils.settings import is_manual_photo_moderation_enabled
    await state.set_state(UserStates.in_quest)
    data = await state.get_data()
    file_id = data.get("pending_photo_file_id")
    if not file_id:
        await callback.answer(i18n.get("photo_error", user.language), show_alert=True)
        return
    await state.update_data(pending_photo_file_id=None)
    await callback.message.edit_caption(
        caption=i18n.get("photo_received", user.language),
        reply_markup=None,
    )
    await callback.answer()
    start_time = time.time()
    logger.info(f"[USER {user.telegram_id}] Фото подтверждено, начинаем обработку")
    route_id = data.get("route_id")
    point_id = data.get("point_id")
    status_msg = await callback.message.answer(i18n.get("photo_received", user.language))
    logger.info(f"[USER {user.telegram_id}] Отправлено подтверждение получения фото")
    message = callback.message
    point_repo = PointRepository(session)
    progress_repo = ProgressRepository(session)
    manual_moderation = await is_manual_photo_moderation_enabled(session)
    try:
        logger.info(f"[USER {user.telegram_id}] Скачиваю фото...")
        await status_msg.edit_text(i18n.get("photo_downloading", user.language))
        photo_path = await download_photo_by_file_id(bot, file_id)
        logger.info(f"[USER {user.telegram_id}] Фото скачано: {photo_path}, время: {time.time() - start_time:.2f}с")
        logger.info(f"[USER {user.telegram_id}] Загружаю данные точки...")
        await status_msg.edit_text(i18n.get("photo_loading_data", user.language))
        point = await point_repo.get_with_tasks_and_images(point_id)
        progress = await progress_repo.get(data.get("progress_id"))
        task_index = data.get("task_index", 0)
        total_tasks = data.get("total_tasks", 1)
        logger.info(f"[USER {user.telegram_id}] Данные загружены, время: {time.time() - start_time:.2f}с")
        if manual_moderation:
            logger.info(f"[USER {user.telegram_id}] Режим ручной модерации включен, отправляю фото админам")
            admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
            route_name = data.get("route_name")
            if not route_name:
                route_repo = RouteRepository(session)
                route = await route_repo.get(route_id)
                if route:
                    route_name = get_localized_field(route, 'name', user.language)
                else:
                    route_name = "Неизвестный маршрут"
            await admin_notifier.notify_photo_verification_needed(
                photo_path=photo_path,
                user_id=user.telegram_id,
                username=user.username,
                point_name=get_localized_field(point, 'name', user.language),
                point_id=point_id,
                progress_id=progress.id,
                photo_file_id=file_id,
                route_name=route_name,
                error_reason="Ручная модерация",
                is_manual_moderation=True
            )
            await status_msg.edit_text(i18n.get("photo_checking", user.language, default="🔍 Проверяю фото..."))
            return
        logger.info(f"[USER {user.telegram_id}] [ШАГ 1/3] Проверяю на фрод...")
        await status_msg.edit_text(i18n.get("checking_antifraud", user.language))
        antifraud_service = AntiFraudService()
        fraud_ok, fraud_messages = await antifraud_service.perform_all_checks(
            photo_path,
            user.telegram_id,
            route_id,
            progress.current_point_order,
            session=session,
            progress=progress,
        )
        del antifraud_service
        gc.collect()
        logger.info(f"[USER {user.telegram_id}] Антифрод: {fraud_ok}, время: {time.time() - start_time:.2f}с")
        if not fraud_ok:
            admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
            await admin_notifier.notify_photo_verification_needed(
                photo_path=photo_path,
                user_id=user.telegram_id,
                username=user.username,
                point_name=point.name,
                point_id=point_id,
                progress_id=progress.id,
                photo_file_id=file_id,
                route_name=data.get("route_name", "Неизвестный маршрут"),
                error_reason=fraud_messages[-1]
            )
            await status_msg.edit_text(f"❌ {fraud_messages[-1]}\n\n⏳ {i18n.get('photo_sent_to_admin', user.language)}")
            return
        await session.commit()
        logger.info(f"[USER {user.telegram_id}] [ШАГ 2/3] Считаю количество людей на фото...")
        await status_msg.edit_text(i18n.get("checking_people", user.language))
        pose_service = None
        try:
            pose_service = PoseService(config.vision)
            people_ok, people_msg, people_count = await pose_service.check_people_count(photo_path)
            logger.info(f"[USER {user.telegram_id}] Подсчет людей: {people_count}, время: {time.time() - start_time:.2f}с")
            if not people_ok:
                admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
                await admin_notifier.notify_photo_verification_needed(
                    photo_path=photo_path,
                    user_id=user.telegram_id,
                    username=user.username,
                    point_name=point.name,
                    point_id=point_id,
                    progress_id=progress.id,
                    photo_file_id=file_id,
                    route_name=data.get("route_name", "Неизвестный маршрут"),
                    error_reason=people_msg,
                    people_count=people_count
                )
                await status_msg.edit_text(f"❌ {people_msg}\n\n⏳ {i18n.get('photo_sent_to_admin', user.language)}")
                return
        except Exception as e:
            logger.error(f"[USER {user.telegram_id}] Ошибка при подсчете людей: {e}", exc_info=True)
            admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
            await admin_notifier.notify_photo_verification_needed(
                photo_path=photo_path,
                user_id=user.telegram_id,
                username=user.username,
                point_name=point.name,
                point_id=point_id,
                progress_id=progress.id,
                photo_file_id=file_id,
                route_name=data.get("route_name", "Неизвестный маршрут"),
                error_reason="Ошибка при подсчете людей",
                people_count=0
            )
            await status_msg.edit_text(f"❌ Ошибка проверки людей\n\n⏳ {i18n.get('photo_sent_to_admin', user.language)}")
            return
        finally:
            if pose_service:
                del pose_service
            gc.collect()
            logger.info(f"[USER {user.telegram_id}] PoseService освобождён")
        gc.collect()
        object_description = None
        point_tasks = get_point_tasks(point)
        if not point_tasks:
            task_repo = TaskRepository(session)
            task_models = await task_repo.get_by_point(point.id)
            point_tasks = tasks_from_models(task_models)
        first_task_text = (point_tasks[0].get('task_text_en') if user.language == 'en' and point_tasks[0].get('task_text_en') else point_tasks[0].get('task_text', '')) if point_tasks else ''
        if first_task_text:
            task_text_clean = first_task_text.split('\n')[0].strip()
            if len(task_text_clean) > 200:
                task_text_clean = task_text_clean[:200] + "..."
            object_description = f"{point.name}, {task_text_clean}"
        elif point.name:
            object_description = point.name
        if object_description:
            logger.info(f"[USER {user.telegram_id}] [ШАГ 3/3] Проверяю локацию через CLIP: '{object_description}'...")
            await status_msg.edit_text(i18n.get("checking_location", user.language))
            vision_service = None
            try:
                vision_service = VisionService(config.vision)
                reference_paths = [
                    ref.file_path for ref in point.reference_images if ref.file_path
                ] if point.reference_images else None
                location_ok, score = await vision_service.check_location(
                    photo_path,
                    reference_photo_paths=reference_paths,
                    object_description=object_description,
                    point_name=point.name
                )
                logger.info(f"[USER {user.telegram_id}] Локация: {location_ok}, score: {score:.2%}, время: {time.time() - start_time:.2f}с")
                if not location_ok:
                    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
                    await admin_notifier.notify_photo_verification_needed(
                        photo_path=photo_path,
                        user_id=user.telegram_id,
                        username=user.username,
                        point_name=point.name,
                        point_id=point_id,
                        progress_id=progress.id,
                        photo_file_id=file_id,
                        route_name=data.get("route_name", "Неизвестный маршрут"),
                        error_reason=f"Похоже, это не та локация (уверенность: {score:.1%})",
                        location_match=score * 100
                    )
                    location_msg = i18n.get('location_mismatch', user.language)
                    score_formatted = f"{score:.1%}"
                    await status_msg.edit_text(
                        f"❌ {location_msg.format(score=score_formatted)}\n"
                        f"{i18n.get('try_closer_photo', user.language)}\n\n"
                        f"⏳ {i18n.get('photo_sent_to_admin', user.language)}"
                    )
                    return
            finally:
                if vision_service:
                    if hasattr(vision_service, 'photo_verifier') and vision_service.photo_verifier:
                        vision_service.photo_verifier.cleanup()
                    del vision_service
                gc.collect()
                logger.info(f"[USER {user.telegram_id}] VisionService освобождён")
        total_time = time.time() - start_time
        logger.info(f"[USER {user.telegram_id}] ✅ Все проверки пройдены! Общее время: {total_time:.2f}с")
        try:
            import shutil
            from pathlib import Path
            user_photos_dir = Path("../../photos") / str(user.telegram_id)
            user_photos_dir.mkdir(parents=True, exist_ok=True)
            timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
            file_extension = photo_path.split('.')[-1]
            filename = f"point_{point_id}_{timestamp}.{file_extension}"
            permanent_path = user_photos_dir / filename
            shutil.copy2(photo_path, permanent_path)
            try:
                from PIL import Image, ImageEnhance
                img = Image.open(str(permanent_path))
                if img.mode == 'RGBA':
                    img = img.convert('RGB')
                img = ImageEnhance.Brightness(img).enhance(1.1)
                img = ImageEnhance.Contrast(img).enhance(1.15)
                img = ImageEnhance.Sharpness(img).enhance(1.2)
                img = ImageEnhance.Color(img).enhance(1.1)
                img.save(str(permanent_path), quality=95, optimize=True)
                logger.info(f"[USER {user.telegram_id}] Фото улучшено")
            except Exception as e:
                logger.warning(f"[USER {user.telegram_id}] Не удалось улучшить фото: {e}")
            relative_path = f"/photos/{user.telegram_id}/{filename}"
            from sqlalchemy import text
            await session.execute(
                text(
                    "INSERT INTO user_photos (user_id, point_id, file_id, file_path, file_hash) "
                    "VALUES (:user_id, :point_id, :file_id, :file_path, :file_hash)"
                ),
                {
                    "user_id": user.id,
                    "point_id": point_id,
                    "file_id": file_id,
                    "file_path": relative_path,
                    "file_hash": None,
                }
            )
            await session.commit()
            logger.info(f"[USER {user.telegram_id}] Фото сохранено: {relative_path}")
            await _on_quest_completed(session, user, message)
        except Exception as e:
            logger.error(f"[USER {user.telegram_id}] Ошибка при сохранении фото: {e}", exc_info=True)
        tasks = get_point_tasks(point)
        if not tasks:
            task_repo = TaskRepository(session)
            task_models = await task_repo.get_by_point(point.id)
            tasks = tasks_from_models(task_models)
        if not tasks:
            await status_msg.edit_text("❌ Ошибка: у точки нет заданий")
            return
        next_task_index = task_index + 1
        if next_task_index < len(tasks):
            next_task = tasks[next_task_index]
            task_text_value = next_task.get('task_text_en') if user.language == 'en' and next_task.get('task_text_en') else next_task.get('task_text', '')
            parsed = parse_task_text(task_text_value)
            task_header = f"{next_task_index + 1}/{len(tasks)}\n" if len(tasks) > 1 else ""
            task_msg = task_header + parsed['task']
            if next_task.get('task_type') in ['text', 'riddle']:
                task_msg += f"\n\n✍️ {i18n.get('send_answer_text', user.language)}!"
                task_hint = next_task.get('text_answer_hint')
                if task_hint:
                    task_msg += f"\n💡 {i18n.get('hint', user.language)}: {task_hint}"
                await state.set_state(UserStates.waiting_text_answer)
                await state.update_data(
                    task_index=next_task_index,
                    attempts=0,
                    max_attempts=next_task.get('max_attempts', 3),
                    current_task_id=next_task.get('id'),
                    waiting_for_arrival=False,
                    waiting_for_fact=False,
                )
            else:
                task_msg += f"\n\n{i18n.get('send_photo', user.language)}"
                await state.set_state(UserStates.in_quest)
                await state.update_data(
                    task_index=next_task_index,
                    current_task_id=next_task.get('id'),
                    waiting_for_arrival=False,
                    waiting_for_fact=False,
                )
            await status_msg.edit_text(i18n.get("point_completed", user.language))
            msg_parts = split_long_message(task_msg)
            for part in msg_parts:
                await message.answer(part)
            return
        await status_msg.edit_text(i18n.get("point_completed", user.language))
        current_point_id = point_id
        completed_point_order = getattr(point, 'order', progress.current_point_order)
        logger.info(f"[USER {user.telegram_id}] Завершена точка id={current_point_id}, order={completed_point_order}")
        next_point_data = await point_repo.get_next_point_data(route_id, completed_point_order)
        if not next_point_data:
            completed_count = completed_point_order + 1
            await progress_repo.complete_point(progress, None, None)
            completion_time = datetime.utcnow() - progress.started_at
            minutes = int(completion_time.total_seconds() / 60)
            try:
                from bot.services.certificate import CertificateService
                cert_service = CertificateService(session)
                certs = await cert_service.create_certificates(progress.id)
                if certs.get('ru') or certs.get('en'):
                    logger.info(f"[USER {user.telegram_id}] Сертификаты созданы: {certs}")
            except Exception as cert_error:
                logger.error(f"[USER {user.telegram_id}] Ошибка создания сертификатов: {cert_error}")
            completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
            completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
            completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
            completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
            completion_msg += f"📜 {i18n.get('certificate_ready', user.language)}\n\n"
            completion_msg += f"{i18n.get('thanks', user.language)}"
            await _send_post_quest_flow(message, user, progress.id, completion_msg)
            await _on_quest_completed(session, user, message)
            return
        if next_point_data:
            logger.info(f"[USER {user.telegram_id}] Найдена следующая точка id={next_point_data.id}, order={next_point_data.order}, текущая order={completed_point_order}")
        next_point = await point_repo.get_with_tasks(next_point_data.id) if next_point_data else None
        if not next_point:
            logger.error(f"[USER {user.telegram_id}] Не удалось загрузить следующую точку id={next_point_data.id if next_point_data else 'None'}")
            await message.answer("❌ Ошибка: следующая точка не найдена")
            return
        if next_point.id == current_point_id:
            logger.warning(f"[USER {user.telegram_id}] get_next_point вернул ту же точку (id={current_point_id}), пропускаем")
            next_point_data = await point_repo.get_next_point_data(route_id, next_point.order)
            if not next_point_data:
                completed_count = completed_point_order + 1
                await progress_repo.complete_point(progress, None, None)
                completion_time = datetime.utcnow() - progress.started_at
                minutes = int(completion_time.total_seconds() / 60)
                completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
                completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
                completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
                completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
                completion_msg += f"{i18n.get('thanks', user.language)}"
                await _send_post_quest_flow(message, user, progress.id, completion_msg)
                await _on_quest_completed(session, user, message)
                return
            next_point = await point_repo.get_with_tasks(next_point_data.id)
            if not next_point:
                await message.answer("❌ Ошибка: следующая точка не найдена")
                return
        if next_point:
            await progress_repo.complete_point(
                progress, next_point.id, next_point.order
            )
            next_tasks = get_point_tasks(next_point)
            if not next_tasks:
                task_repo = TaskRepository(session)
                task_models = await task_repo.get_by_point(next_point.id)
                next_tasks = tasks_from_models(task_models)
            if not next_tasks:
                await message.answer("❌ Ошибка: у следующей точки нет заданий")
                return
            next_task = next_tasks[0]
            next_point_name = get_localized_field(next_point, 'name', user.language)
            task_text_value = next_task.get('task_text_en') if user.language == 'en' and next_task.get('task_text_en') else next_task.get('task_text', '')
            parsed = parse_task_text(task_text_value)
            header = f"{next_point.order}. {next_point_name}\n\n"
            messages_to_send = []
            if parsed['directions']:
                directions_msg = header + f"{parsed['directions']}"
                messages_to_send.append(directions_msg)
                header = ""
            audio_text_value = get_localized_field(next_point, 'audio_text', user.language)
            if audio_text_value:
                audio_msg = (header + f"{audio_text_value}") if header else f"{audio_text_value}"
                messages_to_send.append(audio_msg)
                header = ""
            from aiogram.utils.keyboard import InlineKeyboardBuilder
            from aiogram.types import InlineKeyboardButton
            from bot.utils.helpers import yandex_maps_url
            keyboard_builder = InlineKeyboardBuilder()
            if getattr(user, "show_map", False) and getattr(next_point, "latitude", None) is not None and getattr(next_point, "longitude", None) is not None:
                keyboard_builder.row(
                    InlineKeyboardButton(
                        text=i18n.get("yandex_maps_button", user.language),
                        url=yandex_maps_url(next_point.latitude, next_point.longitude),
                    )
                )
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=i18n.get("i_am_here", user.language),
                    callback_data=f"i_am_here:{next_point.id}:0",
                )
            )
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=i18n.get("cancel_quest", user.language),
                    callback_data=f"cancel_quest:{route_id}",
                )
            )
            await state.set_state(UserStates.in_quest)
            await state.update_data(
                point_id=next_point.id,
                task_index=0,
                total_tasks=len(next_tasks),
                current_task_id=next_task.get('id'),
                waiting_for_arrival=True,
                waiting_for_fact=False,
            )
            for i, msg_text in enumerate(messages_to_send):
                msg_parts = split_long_message(msg_text)
                is_last = (i == len(messages_to_send) - 1)
                for j, part in enumerate(msg_parts):
                    is_last_part = (j == len(msg_parts) - 1)
                    if is_last and is_last_part:
                        await message.answer(part, reply_markup=keyboard_builder.as_markup())
                    else:
                        await message.answer(part)
            if not messages_to_send:
                await message.answer(header.strip(), reply_markup=keyboard_builder.as_markup())
        else:
            completed_count = completed_point_order + 1
            await progress_repo.complete_point(progress, None, None)
            completion_time = datetime.utcnow() - progress.started_at
            minutes = int(completion_time.total_seconds() / 60)
            try:
                from bot.services.certificate import CertificateService
                cert_service = CertificateService(session)
                certs = await cert_service.create_certificates(progress.id)
                if certs.get('ru') or certs.get('en'):
                    logger.info(f"[USER {user.telegram_id}] Сертификаты созданы: {certs}")
            except Exception as cert_error:
                logger.error(f"[USER {user.telegram_id}] Ошибка создания сертификатов: {cert_error}")
            completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
            completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
            completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
            completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
            completion_msg += f"📜 {i18n.get('certificate_ready', user.language)}\n\n"
            completion_msg += f"{i18n.get('thanks', user.language)}"
            await _send_post_quest_flow(message, user, progress.id, completion_msg)
            await _on_quest_completed(session, user, message)
            await state.clear()
    except Exception as e:
        from bot.utils.i18n import i18n
        logger.error(f"[USER {user.telegram_id}] ❌ ОШИБКА при обработке фото: {type(e).__name__}: {str(e)}", exc_info=True)
        try:
            await status_msg.edit_text(
                f"{i18n.get('photo_error', user.language)}\n"
                f"{i18n.get('photo_error_try_again', user.language)}\n\n"
                f"{i18n.get('error_code', user.language)} {type(e).__name__}"
            )
        except Exception as edit_err:
            logger.warning(f"[USER {user.telegram_id}] Не удалось отредактировать сообщение об ошибке: {edit_err}")
            await message.answer(
                f"{i18n.get('photo_error', user.language)}\n"
                f"{i18n.get('photo_error_try_again', user.language)}"
            )
    finally:
        try:
            if 'photo_path' in locals() and os.path.exists(photo_path):
                os.remove(photo_path)
                logger.info(f"[USER {user.telegram_id}] Временное фото удалено")
        except Exception as e:
            logger.error(f"[USER {user.telegram_id}] Ошибка при удалении фото: {e}")
@router.message(StateFilter(UserStates.waiting_text_answer))
async def process_text_answer(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.services.text_validator import TextValidator
    data = await state.get_data()
    waiting_for_arrival = data.get("waiting_for_arrival", False)
    waiting_for_fact = data.get("waiting_for_fact", False)
    if waiting_for_arrival or waiting_for_fact:
        from bot.utils.i18n import i18n
        if waiting_for_arrival:
            await message.answer(i18n.get("i_am_here", user.language, default="✅ Сначала нажмите кнопку 'Я на месте'"))
        else:
            await message.answer(i18n.get("proceed_to_task", user.language, default="▶️ Сначала нажмите кнопку 'Приступить к заданию'"))
        return
    point_id = data.get("point_id")
    task_index = data.get("task_index", 0)
    total_tasks = data.get("total_tasks", 1)
    attempts = data.get("attempts", 0)
    max_attempts = data.get("max_attempts", 3)
    point_repo = PointRepository(session)
    point = await point_repo.get_with_tasks(point_id)
    if not point:
        await message.answer("❌ Ошибка: точка не найдена")
        return
    tasks = get_point_tasks(point)
    if not tasks:
        task_repo = TaskRepository(session)
        task_models = await task_repo.get_by_point(point.id)
        tasks = tasks_from_models(task_models)
    if not tasks or task_index >= len(tasks):
        await message.answer("❌ Ошибка: задание не найдено")
        return
    current_task = tasks[task_index]
    if not current_task.get('text_answer'):
        await message.answer("❌ Ошибка: у задания нет правильного ответа")
        return
    validator = TextValidator()
    raw_answer = current_task.get('text_answer', '')
    correct_answers = [a.strip() for a in raw_answer.split('|') if a.strip()]
    if len(correct_answers) > 1:
        is_correct, similarity, matched_answer = validator.check_multiple_answers(
            message.text,
            correct_answers,
            current_task.get('accept_partial_match', False)
        )
    else:
        is_correct, similarity = validator.check_answer(
            message.text,
            raw_answer,
            current_task.get('accept_partial_match', False)
        )
    from bot.utils.i18n import i18n, get_localized_field
    if is_correct:
        next_task_index = task_index + 1
        if next_task_index < len(tasks):
            next_task = tasks[next_task_index]
            task_text_value = next_task.get('task_text_en') if user.language == 'en' and next_task.get('task_text_en') else next_task.get('task_text', '')
            parsed = parse_task_text(task_text_value)
            task_header = f"{next_task_index + 1}/{len(tasks)}\n" if len(tasks) > 1 else ""
            task_msg = task_header + parsed['task']
            if next_task.get('task_type') in ['text', 'riddle']:
                task_msg += f"\n\n✍️ {i18n.get('send_answer_text', user.language)}!"
                task_hint = next_task.get('text_answer_hint')
                if task_hint:
                    task_msg += f"\n💡 {i18n.get('hint', user.language)}: {task_hint}"
                await state.set_state(UserStates.waiting_text_answer)
                await state.update_data(
                    task_index=next_task_index,
                    attempts=0,
                    max_attempts=next_task.get('max_attempts', 3),
                    current_task_id=next_task.get('id'),
                    waiting_for_arrival=False,
                    waiting_for_fact=False,
                )
            else:
                task_msg += f"\n\n{i18n.get('send_photo', user.language)}"
                await state.set_state(UserStates.in_quest)
                await state.update_data(
                    task_index=next_task_index,
                    current_task_id=next_task.get('id'),
                    waiting_for_arrival=False,
                    waiting_for_fact=False,
                )
            msg_parts = split_long_message(task_msg)
            for part in msg_parts:
                await message.answer(part)
            return
        progress_repo = ProgressRepository(session)
        progress = await progress_repo.get(data.get("progress_id"))
        route_id = data.get("route_id")
        current_point_id = data.get("point_id")
        current_point = await point_repo.get(current_point_id)
        completed_point_order = current_point.order if current_point else progress.current_point_order
        logger.info(f"[USER {user.telegram_id}] Завершена точка id={current_point_id}, order={completed_point_order}")
        next_point_data = await point_repo.get_next_point_data(route_id, completed_point_order)
        if next_point_data:
            logger.info(f"[USER {user.telegram_id}] Найдена следующая точка id={next_point_data.id}, order={next_point_data.order}, текущая order={completed_point_order}")
        if not next_point_data:
            completed_count = completed_point_order + 1
            await progress_repo.complete_point(progress, None, None)
            completion_time = datetime.utcnow() - progress.started_at
            minutes = int(completion_time.total_seconds() / 60)
            try:
                from bot.services.certificate import CertificateService
                cert_service = CertificateService(session)
                certs = await cert_service.create_certificates(progress.id)
                if certs.get('ru') or certs.get('en'):
                    logger.info(f"[USER {user.telegram_id}] Сертификаты созданы: {certs}")
            except Exception as cert_error:
                logger.error(f"[USER {user.telegram_id}] Ошибка создания сертификатов: {cert_error}")
            completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
            completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
            completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
            completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
            completion_msg += f"📜 {i18n.get('certificate_ready', user.language)}\n\n"
            completion_msg += f"{i18n.get('thanks', user.language)}"
            await _send_post_quest_flow(message, user, progress.id, completion_msg)
            await _on_quest_completed(session, user, message)
            return
        next_point = await point_repo.get_with_tasks(next_point_data.id)
        if not next_point:
            logger.error(f"[USER {user.telegram_id}] Не удалось загрузить следующую точку id={next_point_data.id}")
            await message.answer("❌ Ошибка: следующая точка не найдена")
            return
        if next_point.id == current_point_id:
            logger.warning(f"[USER {user.telegram_id}] get_next_point вернул ту же точку (id={current_point_id}), пропускаем")
            next_point_data = await point_repo.get_next_point_data(route_id, next_point.order)
            if not next_point_data:
                completed_count = completed_point_order + 1
                await progress_repo.complete_point(progress, None, None)
                completion_time = datetime.utcnow() - progress.started_at
                minutes = int(completion_time.total_seconds() / 60)
                completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
                completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
                completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
                completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
                completion_msg += f"{i18n.get('thanks', user.language)}"
                await _send_post_quest_flow(message, user, progress.id, completion_msg)
                await _on_quest_completed(session, user, message)
                return
            next_point = await point_repo.get_with_tasks(next_point_data.id)
        if next_point:
            await progress_repo.complete_point(progress, next_point.id, next_point.order)
            next_tasks = get_point_tasks(next_point)
            if not next_tasks:
                task_repo = TaskRepository(session)
                task_models = await task_repo.get_by_point(next_point.id)
                next_tasks = tasks_from_models(task_models)
            if not next_tasks:
                await message.answer("❌ Ошибка: у следующей точки нет заданий")
                return
            next_task = next_tasks[0]
            next_point_name = get_localized_field(next_point, 'name', user.language)
            task_text_value = next_task.get('task_text_en') if user.language == 'en' and next_task.get('task_text_en') else next_task.get('task_text', '')
            parsed = parse_task_text(task_text_value)
            header = f"{next_point.order}. {next_point_name}\n\n"
            messages_to_send = []
            if parsed['directions']:
                directions_msg = header + f"{parsed['directions']}"
                messages_to_send.append(directions_msg)
                header = ""
            audio_text_value = get_localized_field(next_point, 'audio_text', user.language)
            if audio_text_value:
                audio_msg = (header + f"{audio_text_value}") if header else f"{audio_text_value}"
                messages_to_send.append(audio_msg)
                header = ""
            from aiogram.utils.keyboard import InlineKeyboardBuilder
            from aiogram.types import InlineKeyboardButton
            from bot.utils.helpers import yandex_maps_url
            keyboard_builder = InlineKeyboardBuilder()
            if getattr(user, "show_map", False) and getattr(next_point, "latitude", None) is not None and getattr(next_point, "longitude", None) is not None:
                keyboard_builder.row(
                    InlineKeyboardButton(
                        text=i18n.get("yandex_maps_button", user.language),
                        url=yandex_maps_url(next_point.latitude, next_point.longitude),
                    )
                )
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=i18n.get("i_am_here", user.language),
                    callback_data=f"i_am_here:{next_point.id}:0",
                )
            )
            keyboard_builder.row(
                InlineKeyboardButton(
                    text=i18n.get("cancel_quest", user.language),
                    callback_data=f"cancel_quest:{route_id}",
                )
            )
            await state.set_state(UserStates.in_quest)
            await state.update_data(
                point_id=next_point.id,
                task_index=0,
                total_tasks=len(next_tasks),
                current_task_id=next_task.get('id'),
                waiting_for_arrival=True,
                waiting_for_fact=False,
            )
            for i, msg_text in enumerate(messages_to_send):
                msg_parts = split_long_message(msg_text)
                is_last = (i == len(messages_to_send) - 1)
                for j, part in enumerate(msg_parts):
                    is_last_part = (j == len(msg_parts) - 1)
                    if is_last and is_last_part:
                        await message.answer(part, reply_markup=keyboard_builder.as_markup())
                    else:
                        await message.answer(part)
            if not messages_to_send:
                await message.answer(header.strip(), reply_markup=keyboard_builder.as_markup())
        else:
            completed_count = completed_point_order + 1
            await progress_repo.complete_point(progress, None, None)
            completion_time = datetime.utcnow() - progress.started_at
            minutes = int(completion_time.total_seconds() / 60)
            try:
                from bot.services.certificate import CertificateService
                cert_service = CertificateService(session)
                certs = await cert_service.create_certificates(progress.id)
                if certs.get('ru') or certs.get('en'):
                    logger.info(f"[USER {user.telegram_id}] Сертификаты созданы: {certs}")
            except Exception as cert_error:
                logger.error(f"[USER {user.telegram_id}] Ошибка создания сертификатов: {cert_error}")
            completion_msg = f"🎉 <b>{i18n.get('quest_completed', user.language)}!</b>\n\n"
            completion_msg += f"📊 {i18n.get('stats_completed', user.language)}:\n"
            completion_msg += f"• {i18n.get('completed_points', user.language)} {progress.points_completed}\n"
            completion_msg += f"• {i18n.get('time_spent', user.language)} {format_duration(minutes)}\n\n"
            completion_msg += f"📜 {i18n.get('certificate_ready', user.language)}\n\n"
            completion_msg += f"{i18n.get('thanks', user.language)}"
            await _send_post_quest_flow(message, user, progress.id, completion_msg)
            await _on_quest_completed(session, user, message)
            await state.clear()
    else:
        attempts += 1
        await state.update_data(attempts=attempts)
        if attempts >= max_attempts:
            correct_answer_display = current_task.get('text_answer', '')
            if '|' in correct_answer_display:
                answers_list = [a.strip() for a in correct_answer_display.split('|') if a.strip()]
                correct_answer_display = ' / '.join(answers_list)
            await message.answer(
                f"❌ {i18n.get('incorrect_answer', user.language, default='Неправильно')}. "
                f"{i18n.get('attempts_exhausted', user.language, default='Попытки исчерпаны')} ({attempts}/{max_attempts}).\n\n"
                f"{i18n.get('correct_answer_is', user.language, default='Правильный ответ')}: <b>{correct_answer_display}</b>\n\n"
                f"{i18n.get('try_again_from_start', user.language, default='Попробуйте снова с начала квеста или обратитесь к администратору')}.",
                parse_mode="HTML"
            )
            await state.clear()
        else:
            await message.answer(
                f"❌ {i18n.get('incorrect_answer', user.language, default='Неправильно')}. "
                f"{i18n.get('try_again', user.language, default='Попробуйте еще раз')}.\n\n"
                f"{i18n.get('similarity', user.language, default='Похожесть')}: {similarity*100:.0f}%\n"
                f"{i18n.get('attempts_left', user.language, default='Попыток осталось')}: {max_attempts - attempts}"
            )
@router.callback_query(F.data.startswith("cancel_quest:"))
async def cancel_quest(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.clear()
    from bot.utils.i18n import i18n
    await callback.message.edit_text(
        i18n.get("quest_cancelled", user.language, default="❌ Квест прерван.\n\nВы можете начать заново в любой момент."),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
    await set_user_commands(bot, callback.from_user.id, user.language, in_quest=False)
    await callback.answer()
@router.message(Command("cancel_quest"))
async def cmd_cancel_quest(message: Message, user: User, session: AsyncSession, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    if not data.get("progress_id"):
        await message.answer(
            i18n.get("no_active_quest", user.language),
            reply_markup=UserKeyboards.no_active_quest_keyboard(user.language),
        )
        return
    await state.clear()
    await message.answer(
        i18n.get("quest_cancelled", user.language, default="❌ Квест прерван.\n\nВы можете начать заново в любой момент."),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
    await set_user_commands(bot, message.from_user.id, user.language, in_quest=False)
@router.message(Command("restart_point"))
async def cmd_restart_point(message: Message, user: User, session: AsyncSession, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    progress_id = data.get("progress_id")
    point_id = data.get("point_id")
    if not progress_id or not point_id:
        await message.answer(i18n.get("no_active_quest", user.language))
        return
    point_repo = PointRepository(session)
    point = await point_repo.get_with_tasks(point_id)
    if not point:
        await message.answer(i18n.get("restart_point_point_not_found", user.language, default="❌ Точка не найдена."))
        return
    tasks = get_point_tasks(point)
    if not tasks:
        task_repo = TaskRepository(session)
        task_models = await task_repo.get_by_point(point.id)
        tasks = tasks_from_models(task_models)
    if not tasks:
        await message.answer(i18n.get("restart_point_no_tasks", user.language, default="❌ У этой точки нет заданий."))
        return
    first_task = tasks[0]
    progress_repo = ProgressRepository(session)
    progress = await progress_repo.get(progress_id)
    await state.set_state(UserStates.in_quest)
    await state.update_data(
        task_index=0,
        total_tasks=len(tasks),
        current_task_id=first_task.get('id'),
        waiting_for_arrival=True,
        waiting_for_fact=False,
    )
    point_name = get_localized_field(point, 'name', user.language)
    task_text_value = first_task.get('task_text_en') if user.language == 'en' and first_task.get('task_text_en') else first_task.get('task_text', '')
    parsed = parse_task_text(task_text_value)
    order_num = point.order if point.order else (progress.current_point_order + 1 if progress else 1)
    header = f"🔄 {order_num}. {point_name}\n\n"
    messages_to_send = []
    if parsed['directions']:
        messages_to_send.append(header + parsed['directions'])
        header = ""
    audio_text_value = get_localized_field(point, 'audio_text', user.language)
    if audio_text_value:
        audio_msg = (header + audio_text_value) if header else audio_text_value
        messages_to_send.append(audio_msg)
        header = ""
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from bot.utils.helpers import yandex_maps_url
    keyboard_builder = InlineKeyboardBuilder()
    if getattr(user, "show_map", False) and getattr(point, "latitude", None) is not None and getattr(point, "longitude", None) is not None:
        keyboard_builder.row(
            InlineKeyboardButton(
                text=i18n.get("yandex_maps_button", user.language),
                url=yandex_maps_url(point.latitude, point.longitude),
            )
        )
    keyboard_builder.row(InlineKeyboardButton(
        text=i18n.get("i_am_here", user.language, default="📍 Я на месте"),
        callback_data=f"i_am_here:{point.id}:0",
    ))
    route_id = data.get("route_id")
    if route_id:
        keyboard_builder.row(InlineKeyboardButton(
            text=i18n.get("cancel_quest", user.language, default="❌ Выйти из квеста"),
            callback_data=f"cancel_quest:{route_id}",
        ))
    for i, msg_text in enumerate(messages_to_send):
        msg_parts = split_long_message(msg_text)
        is_last = (i == len(messages_to_send) - 1)
        for j, part in enumerate(msg_parts):
            is_last_part = (j == len(msg_parts) - 1)
            if is_last and is_last_part:
                await message.answer(part, reply_markup=keyboard_builder.as_markup())
            else:
                await message.answer(part)
    if not messages_to_send:
        await message.answer(header.strip(), reply_markup=keyboard_builder.as_markup())
@router.callback_query(F.data.startswith("pause_quest:"))
async def pause_quest(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    route_id = int(callback.data.split(":")[1])
    progress_repo = ProgressRepository(session)
    progress = await progress_repo.get_active_progress(user.id, route_id)
    if not progress:
        await callback.answer(i18n.get("quest_not_found", user.language, default="Квест не найден"), show_alert=True)
        return
    await progress_repo.pause_quest(progress)
    route_repo = RouteRepository(session)
    route = await route_repo.get(route_id)
    from bot.utils.i18n import get_localized_field
    route_name = get_localized_field(route, 'name', user.language) if route else "Квест"
    paused_mins = progress.total_paused_seconds // 60
    text = (
        f"⏸️ <b>{i18n.get('quest_paused', user.language, default='Квест на паузе')}</b>\n\n"
        f"🗺 {route_name}\n"
        f"📍 {i18n.get('completed_points', user.language, default='Пройдено точек')}: {progress.points_completed}\n\n"
        f"{i18n.get('pause_info', user.language, default='Вы можете продолжить квест в любой момент. Весь прогресс сохранён.')}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=UserKeyboards.paused_quest_menu(route_id, user.language),
        parse_mode="HTML"
    )
    await callback.answer(i18n.get("quest_paused_short", user.language, default="Квест поставлен на паузу"))
@router.callback_query(F.data.startswith("resume_quest:"))
async def resume_quest(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n, get_localized_field
    route_id = int(callback.data.split(":")[1])
    progress_repo = ProgressRepository(session)
    progress = await progress_repo.get_active_or_paused_progress(user.id, route_id)
    if not progress:
        await callback.answer(i18n.get("quest_not_found", user.language, default="Квест не найден"), show_alert=True)
        return
    await progress_repo.resume_quest(progress)
    route_repo = RouteRepository(session)
    route = await route_repo.get(route_id)
    point_repo = PointRepository(session)
    current_point = await point_repo.get(progress.current_point_id)
    if not current_point:
        await callback.answer(i18n.get("point_not_found", user.language, default="Точка не найдена"), show_alert=True)
        return
    route_name = get_localized_field(route, 'name', user.language) if route else "Квест"
    point_name = get_localized_field(current_point, 'name', user.language)
    text = (
        f"▶️ <b>{i18n.get('quest_resumed', user.language, default='Квест продолжается!')}</b>\n\n"
        f"🗺 {route_name}\n"
        f"📍 {i18n.get('current_point', user.language, default='Текущая точка')}: {point_name}\n"
        f"✅ {i18n.get('completed', user.language, default='Пройдено')}: {progress.points_completed} {i18n.get('points', user.language, default='точек')}\n\n"
        f"{i18n.get('continue_quest_info', user.language, default='Продолжайте выполнять задания!')}"
    )
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    builder = InlineKeyboardBuilder()
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("pause_quest", user.language, default="⏸️ Пауза"),
            callback_data=f"pause_quest:{route_id}"
        )
    )
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("cancel_quest", user.language, default="❌ Отменить квест"),
            callback_data=f"cancel_quest:{route_id}"
        )
    )
    await callback.message.edit_text(text, reply_markup=builder.as_markup(), parse_mode="HTML")
    await callback.answer(i18n.get("quest_resumed_short", user.language, default="Квест продолжен!"))
@router.callback_query(F.data == "my_stats")
async def my_stats(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    from bot.utils.i18n import i18n
    import logging
    logger = logging.getLogger(__name__)
    try:
        progress_repo = ProgressRepository(session)
        stats = await progress_repo.get_user_stats(user.id)
        total_routes = stats.get('total_routes', 0)
        completed = stats.get('completed', 0)
        in_progress = stats.get('in_progress', 0)
        total_points = stats.get('total_points', 0)
        total_photos = stats.get('total_photos', 0)
        longest_quest = stats.get('longest_quest', 0)
        shortest_quest = stats.get('shortest_quest', 0)
        user_rank = stats.get('user_rank', 1)
        text = f"📊 <b>{i18n.get('your_stats', user.language)}</b>\n\n"
        text += f"🗺 {i18n.get('total_routes', user.language)}: {total_routes}\n"
        text += f"✅ {i18n.get('completed', user.language)}: {completed}\n"
        text += f"⏳ {i18n.get('in_progress', user.language)}: {in_progress}\n"
        text += f"📍 {i18n.get('total_points_completed', user.language)}: {total_points}\n"
        text += f"📸 Сделано фото: {total_photos}\n\n"
        if longest_quest > 0:
            text += f"⏱ <b>Рекорды:</b>\n"
            text += f"• Самый длинный квест: {format_duration(longest_quest)}\n"
            if shortest_quest > 0:
                text += f"• Самый быстрый квест: {format_duration(shortest_quest)}\n"
            text += f"\n"
        text += f"🏆 Ваша позиция: #{user_rank}\n"
    except Exception as e:
        logger.error(f"Error getting stats for user {user.id}: {e}")
        text = f"📊 <b>{i18n.get('your_stats', user.language)}</b>\n\n"
        text += "⚠️ Не удалось загрузить статистику. Попробуйте позже.\n"
    try:
        await callback.message.edit_text(
            text,
            reply_markup=UserKeyboards.main_menu(user.language),
            parse_mode="HTML",
        )
    except Exception:
        pass
    await callback.answer()
@router.callback_query(F.data.startswith("lang:"))
async def language_selected(callback: CallbackQuery, user: User, session: AsyncSession, state: FSMContext):
    from bot.utils.i18n import i18n
    language = callback.data.split(":")[1]
    if language not in ['ru', 'en']:
        language = 'ru'
    user.language = language
    await session.commit()
    await session.refresh(user)
    await callback.message.edit_text(
        i18n.get("welcome", language, name=user.first_name),
        reply_markup=UserKeyboards.main_menu(language),
    )
    await state.clear()
    await set_user_commands(bot, callback.from_user.id, language, in_quest=False)
    await callback.answer(i18n.get("language_changed", language) or ("✅ Язык изменен!" if language == "ru" else "✅ Language changed!"))
@router.callback_query(F.data == "settings")
async def settings_menu(callback: CallbackQuery, user: User):
    from bot.utils.i18n import i18n
    await callback.message.edit_text(
        i18n.get("settings_menu", user.language),
        reply_markup=UserKeyboards.settings_menu(user.language, getattr(user, "show_map", False)),
    )
    await callback.answer()
@router.callback_query(F.data == "settings:show_map")
async def settings_toggle_show_map(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    user.show_map = not getattr(user, "show_map", False)
    await session.commit()
    await session.refresh(user)
    await callback.message.edit_text(
        i18n.get("settings_menu", user.language),
        reply_markup=UserKeyboards.settings_menu(user.language, user.show_map),
    )
    status = i18n.get("show_map_on", user.language) if user.show_map else i18n.get("show_map_off", user.language)
    await callback.answer(status)
@router.callback_query(F.data == "settings:language")
async def settings_change_language(callback: CallbackQuery, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    await callback.message.edit_text(
        i18n.get("choose_language_prompt", user.language),
        reply_markup=UserKeyboards.language_selection(),
    )
    await state.set_state(UserStates.selecting_language)
    await callback.answer()
@router.callback_query(F.data == "settings:audio")
async def settings_audio(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from sqlalchemy import select
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=False,
            language=user.language,
            voice_id=0,
            speech_rate=150
        )
        session.add(audio_settings)
        await session.commit()
        await session.refresh(audio_settings)
        await session.refresh(user)
    auto_play_text = i18n.get("audio_autoplay_on", user.language) if audio_settings.auto_play else i18n.get("audio_autoplay_off", user.language)
    voice_id = audio_settings.voice_id if audio_settings.voice_id is not None else 0
    voice_text = i18n.get("audio_voice_male", user.language) if voice_id == 0 else i18n.get("audio_voice_female", user.language)
    speech_rate = audio_settings.speech_rate if audio_settings.speech_rate is not None else 150
    rate_text = f"{speech_rate} {i18n.get('audio_rate_words', user.language)}"
    text = (
        f"🎧 <b>{i18n.get('audio_settings', user.language)}</b>\n\n"
        f"• {i18n.get('audio_autoplay', user.language)}: {auto_play_text}\n"
        f"• {i18n.get('audio_voice', user.language)}: {voice_text}\n"
        f"• {i18n.get('audio_rate', user.language)}: {rate_text}\n\n"
        f"{i18n.get('audio_choose_setting', user.language)}"
    )
    from bot.keyboards.user import UserKeyboards
    await callback.message.edit_text(
        text,
        reply_markup=UserKeyboards.get_audio_settings_keyboard(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "audio_settings:toggle_autoplay")
async def audio_toggle_autoplay(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from sqlalchemy import select
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=True,
            language=user.language,
            voice_id=0,
            speech_rate=150
        )
        session.add(audio_settings)
    else:
        audio_settings.auto_play = not audio_settings.auto_play
    await session.commit()
    await session.refresh(user)
    await settings_audio(callback, user, session)
@router.callback_query(F.data == "audio_settings:voice")
async def audio_select_voice(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from sqlalchemy import select
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=False,
            language=user.language,
            voice_id=0,
            speech_rate=150
        )
        session.add(audio_settings)
        await session.commit()
    current_voice = (audio_settings.voice_id if audio_settings.voice_id is not None else 0) if audio_settings else 0
    builder = InlineKeyboardBuilder()
    builder.row(
        InlineKeyboardButton(
            text=f"{'✅ ' if current_voice == 0 else ''}{i18n.get('audio_voice_male', user.language)}",
            callback_data="audio_settings:set_voice:0"
        )
    )
    builder.row(
        InlineKeyboardButton(
            text=f"{'✅ ' if current_voice == 1 else ''}{i18n.get('audio_voice_female', user.language)}",
            callback_data="audio_settings:set_voice:1"
        )
    )
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("back", user.language),
            callback_data="settings:audio"
        )
    )
    await callback.message.edit_text(
        f"👤 <b>{i18n.get('audio_select_voice', user.language)}</b>",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("audio_settings:set_voice:"))
async def audio_set_voice(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from sqlalchemy import select
    voice_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=False,
            language=user.language,
            voice_id=voice_id,
            speech_rate=150
        )
        session.add(audio_settings)
    else:
        audio_settings.voice_id = voice_id
    await session.commit()
    await session.refresh(user)
    await callback.answer(i18n.get("audio_voice_changed", user.language))
    await settings_audio(callback, user, session)
@router.callback_query(F.data == "audio_settings:rate")
async def audio_select_rate(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from sqlalchemy import select
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=False,
            language=user.language,
            voice_id=0,
            speech_rate=150
        )
        session.add(audio_settings)
        await session.commit()
    current_rate = (audio_settings.speech_rate if audio_settings.speech_rate is not None else 150) if audio_settings else 150
    rates = [100, 120, 150, 180, 200]
    builder = InlineKeyboardBuilder()
    for rate in rates:
        builder.row(
            InlineKeyboardButton(
                text=f"{'✅ ' if rate == current_rate else ''}⚡ {rate} {i18n.get('audio_rate_words', user.language)}",
                callback_data=f"audio_settings:set_rate:{rate}"
            )
        )
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("back", user.language),
            callback_data="settings:audio"
        )
    )
    await callback.message.edit_text(
        f"⚡ <b>{i18n.get('audio_select_rate', user.language)}</b>",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("audio_settings:set_rate:"))
async def audio_set_rate(callback: CallbackQuery, user: User, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.models.user_audio_settings import UserAudioSettings
    from sqlalchemy import select
    rate = int(callback.data.split(":")[-1])
    result = await session.execute(
        select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
    )
    audio_settings = result.scalar_one_or_none()
    if not audio_settings:
        audio_settings = UserAudioSettings(
            user_id=user.id,
            auto_play=False,
            language=user.language,
            voice_id=0,
            speech_rate=rate
        )
        session.add(audio_settings)
    else:
        audio_settings.speech_rate = rate
    await session.commit()
    await session.refresh(user)
    await callback.answer(i18n.get("audio_rate_changed", user.language))
    await settings_audio(callback, user, session)
@router.message(Command("top"))
async def cmd_top(message: Message, session: AsyncSession, user: User):
    from bot.utils.i18n import i18n
    route_repo = RouteRepository(session)
    top_routes = await route_repo.get_top_routes(limit=10)
    if not top_routes:
        await message.answer(
            f"📈 <b>{i18n.get('top_routes_title', user.language)}</b>\n\n"
            f"{i18n.get('top_routes_empty', user.language)}",
            parse_mode="HTML"
        )
        return
    text = f"📈 <b>{i18n.get('top_routes_list', user.language)}</b>\n\n"
    text += f"{i18n.get('top_routes_popular', user.language)}\n\n"
    for i, (route, completions, avg_time) in enumerate(top_routes, 1):
        medal = {1: "🥇", 2: "🥈", 3: "🥉"}.get(i, f"{i}.")
        route_name = get_localized_field(route, 'name', user.language)
        text += f"{medal} <b>{route_name}</b>\n"
        text += f"   👥 {i18n.get('top_completions', user.language)}: {completions}\n"
        if avg_time > 0:
            text += f"   ⏱ {i18n.get('top_avg_time', user.language)}: {format_duration(avg_time)}\n"
        text += f"   💰 {i18n.get('top_price', user.language)}: {route.price} грошей\n"
        text += f"   🌐 <a href='{config.web.site_url}/routes/view.php?id={route.id}'>{i18n.get('top_more', user.language)}</a>\n\n"
    await message.answer(text, parse_mode="HTML", disable_web_page_preview=True)
@router.callback_query(F.data == "check_subscription")
async def check_subscription(callback: CallbackQuery, user: User):
    from bot.loader import bot
    from bot.utils.i18n import i18n
    from aiogram.exceptions import TelegramBadRequest
    try:
        user_id = user.telegram_id if hasattr(user, 'telegram_id') else user.id
        member = None
        if config.channel.channel_id and config.channel.channel_id != 0:
            try:
                channel_id = config.channel.channel_id
                if channel_id > 0:
                    channel_id = -1000000000000 - channel_id
                member = await bot.get_chat_member(
                    chat_id=channel_id,
                    user_id=user_id
                )
            except TelegramBadRequest as e:
                error_msg = str(e).lower()
                if "member list is inaccessible" in error_msg or "chat not found" in error_msg:
                    if config.channel.channel_username:
                        try:
                            member = await bot.get_chat_member(
                                chat_id=f"@{config.channel.channel_username}",
                                user_id=user_id
                            )
                        except TelegramBadRequest:
                            await callback.answer(i18n.get("subscribe_fail", user.language), show_alert=True)
                            return
                    else:
                        await callback.answer(i18n.get("subscribe_fail", user.language), show_alert=True)
                        return
                else:
                    raise
        elif config.channel.channel_username:
            try:
                member = await bot.get_chat_member(
                    chat_id=f"@{config.channel.channel_username}",
                    user_id=user_id
                )
            except TelegramBadRequest:
                await callback.answer(i18n.get("subscribe_fail", user.language), show_alert=True)
                return
        else:
            await callback.answer("Канал не настроен", show_alert=True)
            return
        if member is None:
            await callback.answer(i18n.get("subscribe_fail", user.language), show_alert=True)
            return
        status_val = getattr(member.status, 'value', str(member.status)).lower() if member.status else ''
        is_subscribed = status_val in ('member', 'administrator', 'creator')
        if is_subscribed:
            await callback.message.edit_text(
                f"{i18n.get('subscribe_success', user.language)}\n\n"
                f"{i18n.get('main_menu', user.language)}",
                reply_markup=UserKeyboards.main_menu(user.language),
                parse_mode="HTML"
            )
            await callback.answer("✅ Подписка подтверждена!")
        else:
            await callback.answer(
                i18n.get("subscribe_fail", user.language),
                show_alert=True
            )
    except Exception as e:
        logger.error(f"Ошибка проверки подписки: {e}")
        await callback.answer("Ошибка проверки подписки", show_alert=True)
@router.callback_query(F.data == "about")
async def about(callback: CallbackQuery, user: User):
    from bot.utils.i18n import i18n
    await callback.message.edit_text(
        i18n.get("about_bot", user.language),
        reply_markup=UserKeyboards.main_menu(user.language),
    )
    await callback.answer()
@router.message(
    F.text.startswith("/")
    & ~F.text.regexp(r"^/(start|web|review|top|promo|admin|token|become_creator|partner|commands|restart_point|cancel_quest|skip)(@[\w_]+)?(\s|$)")
)
async def unknown_command(message: Message, user: User):
    from bot.utils.i18n import i18n
    await message.answer(i18n.get("unknown_command", user.language))