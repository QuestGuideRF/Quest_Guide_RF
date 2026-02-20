import logging
from decimal import Decimal
from aiogram import Router, F
from aiogram.types import CallbackQuery, Message
from aiogram.filters import Command, StateFilter
from aiogram.fsm.context import FSMContext
from sqlalchemy.ext.asyncio import AsyncSession
from bot.fsm.states import SurveyStates
from bot.models.user import User
from bot.repositories.survey import SurveyRepository
from bot.services.earnings import award_quest_earnings
from bot.services.platform_settings import PlatformSettingsService
logger = logging.getLogger(__name__)
router = Router()
@router.message(Command("skip"), StateFilter(SurveyStates))
async def survey_skip_all(message: Message, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    from bot.keyboards.user import UserKeyboards
    await state.clear()
    text = i18n.get("congrats_quest_done", user.language, default="Поздравляем с завершением квеста! Спасибо за участие!")
    await message.answer(text, reply_markup=UserKeyboards.post_quest_final(user.language), parse_mode="HTML")
@router.callback_query(F.data.startswith("survey:start:"))
async def survey_start(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    progress_id = int(callback.data.split(":")[2])
    survey_repo = SurveyRepository(session)
    if await survey_repo.has_result(progress_id):
        await callback.answer(
            i18n.get("survey_already_done", user.language, default="Вы уже проходили опрос для этого квеста."),
            show_alert=True,
        )
        return
    from sqlalchemy import text
    row = await session.execute(
        text("SELECT route_id FROM user_progress WHERE id = :pid"),
        {"pid": progress_id},
    )
    progress_row = row.fetchone()
    if not progress_row:
        await callback.answer("Прогресс не найден", show_alert=True)
        return
    route_id = progress_row[0]
    await state.set_state(SurveyStates.question_difficulty)
    await state.update_data(
        survey_progress_id=progress_id,
        survey_route_id=route_id,
        survey_answers={},
    )
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    builder = InlineKeyboardBuilder()
    for i in range(1, 6):
        builder.button(text=str(i), callback_data=f"survey:diff:{i}")
    builder.adjust(5)
    q_text = i18n.get("survey_q_difficulty", user.language, default="📊 Оцените сложность квеста от 1 до 5:")
    await callback.message.edit_text(
        f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (1/5)\n\n{q_text}\n\n{i18n.get('can_skip_cmd', user.language, default='Можно пропустить: /skip')}",
        reply_markup=builder.as_markup(),
        parse_mode="HTML",
    )
    await callback.answer()
@router.callback_query(F.data.startswith("survey:diff:"), SurveyStates.question_difficulty)
async def survey_difficulty(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    val = int(callback.data.split(":")[2])
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    answers["difficulty"] = val
    await state.update_data(survey_answers=answers)
    await state.set_state(SurveyStates.question_navigation)
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    builder = InlineKeyboardBuilder()
    for i in range(1, 6):
        builder.button(text=str(i), callback_data=f"survey:nav:{i}")
    builder.adjust(5)
    q_text = i18n.get("survey_q_navigation", user.language, default="🧭 Оцените удобство навигации от 1 до 5:")
    await callback.message.edit_text(
        f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (2/5)\n\n{q_text}",
        reply_markup=builder.as_markup(),
        parse_mode="HTML",
    )
    await callback.answer()
@router.callback_query(F.data.startswith("survey:nav:"), SurveyStates.question_navigation)
async def survey_navigation(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    val = int(callback.data.split(":")[2])
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    answers["navigation"] = val
    await state.update_data(survey_answers=answers)
    await state.set_state(SurveyStates.question_liked)
    q_text = i18n.get("survey_q_liked", user.language, default="😊 Что понравилось больше всего? (напишите текстом)")
    await callback.message.edit_text(
        f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (3/5)\n\n{q_text}",
        parse_mode="HTML",
    )
    await callback.answer()
@router.message(StateFilter(SurveyStates.question_liked))
async def survey_liked(message: Message, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    answers["liked"] = message.text or ""
    await state.update_data(survey_answers=answers)
    await state.set_state(SurveyStates.question_problems)
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    builder = InlineKeyboardBuilder()
    builder.button(
        text=i18n.get("yes", user.language, default="Да"),
        callback_data="survey:prob:yes",
    )
    builder.button(
        text=i18n.get("no", user.language, default="Нет"),
        callback_data="survey:prob:no",
    )
    builder.adjust(2)
    q_text = i18n.get("survey_q_problems", user.language, default="🔧 Были ли технические проблемы?")
    await message.answer(
        f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (4/5)\n\n{q_text}",
        reply_markup=builder.as_markup(),
        parse_mode="HTML",
    )
@router.callback_query(F.data.startswith("survey:prob:"), SurveyStates.question_problems)
async def survey_problems(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    val = callback.data.split(":")[2]
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    answers["had_problems"] = val == "yes"
    if val == "yes":
        await state.update_data(survey_answers=answers)
        await state.set_state(SurveyStates.question_problems_text)
        q_text = i18n.get("survey_q_problems_desc", user.language, default="Опишите проблему кратко:")
        await callback.message.edit_text(
            f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (4/5)\n\n{q_text}",
            parse_mode="HTML",
        )
        await callback.answer()
    else:
        answers["problems_text"] = ""
        await state.update_data(survey_answers=answers)
        await state.set_state(SurveyStates.question_improve)
        q_text = i18n.get("survey_q_improve", user.language, default="💡 Что можно улучшить? (напишите текстом или /skip)")
        await callback.message.edit_text(
            f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (5/5)\n\n{q_text}",
            parse_mode="HTML",
        )
        await callback.answer()
@router.message(StateFilter(SurveyStates.question_problems_text))
async def survey_problems_text(message: Message, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    answers["problems_text"] = message.text or ""
    await state.update_data(survey_answers=answers)
    await state.set_state(SurveyStates.question_improve)
    q_text = i18n.get("survey_q_improve", user.language, default="💡 Что можно улучшить? (напишите текстом или /skip)")
    await message.answer(
        f"📋 <b>{i18n.get('survey_title', user.language, default='Опрос')}</b> (5/5)\n\n{q_text}",
        parse_mode="HTML",
    )
@router.message(StateFilter(SurveyStates.question_improve))
async def survey_improve(message: Message, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    answers = data.get("survey_answers", {})
    improve_text = message.text or ""
    if improve_text.strip().lower() == "/skip":
        improve_text = ""
    answers["improve"] = improve_text
    progress_id = data["survey_progress_id"]
    route_id = data["survey_route_id"]
    settings = PlatformSettingsService(session)
    reward_enabled = await settings.get_setting_bool("survey_reward_enabled", True)
    reward_amount = await settings.get_setting_decimal("survey_reward_amount", Decimal("5"))
    actual_reward = Decimal("0")
    if reward_enabled and reward_amount > 0:
        actual_reward = await award_quest_earnings(
            session, progress_id, user.id, route_id, reward_amount, "survey"
        )
    survey_repo = SurveyRepository(session)
    await survey_repo.save_result(
        user_id=user.id,
        progress_id=progress_id,
        route_id=route_id,
        answers=answers,
        reward_given=actual_reward,
    )
    route_name = "Неизвестный маршрут"
    try:
        from bot.repositories.route import RouteRepository
        from bot.utils.i18n import get_localized_field
        route_repo = RouteRepository(session)
        route_names = await route_repo.get_route_names(route_id)
        if route_names:
            route_name = get_localized_field(route_names, "name", user.language) or route_names.name or route_name
    except Exception:
        pass
    try:
        from bot.loader import bot, config
        from bot.services.admin_notifier import AdminNotifier
        admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
        await admin_notifier.notify_survey_results(
            user_id=user.id,
            username=user.username,
            route_name=route_name,
            answers=answers,
        )
    except Exception as e:
        logger.warning(f"Не удалось отправить результаты опроса админам: {e}")
    await state.clear()
    result_text = f"✅ <b>{i18n.get('survey_thanks', user.language, default='Спасибо за ваш отзыв!')}</b>\n\n"
    if actual_reward > 0:
        result_text += f"💰 {i18n.get('survey_reward_text', user.language, default='Награда за опрос')}: {int(actual_reward)} {i18n.get('currency_groshi', user.language, default='грошей')}\n\n"
    result_text += i18n.get("congrats_quest_done", user.language, default="Поздравляем с завершением квеста! Спасибо за участие!")
    from bot.keyboards.user import UserKeyboards
    await message.answer(result_text, reply_markup=UserKeyboards.post_quest_after_survey(progress_id, user.language), parse_mode="HTML")