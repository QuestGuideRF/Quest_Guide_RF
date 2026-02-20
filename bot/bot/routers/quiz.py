import logging
from decimal import Decimal
from aiogram import Router, F
from aiogram.types import CallbackQuery
from aiogram.fsm.context import FSMContext
from sqlalchemy.ext.asyncio import AsyncSession
from bot.fsm.states import QuizStates
from bot.models.user import User
from bot.repositories.quiz import QuizRepository
from bot.services.earnings import award_quest_earnings
from bot.services.platform_settings import PlatformSettingsService
logger = logging.getLogger(__name__)
router = Router()
def _localized(question, field, lang):
    if lang == 'en':
        en_val = getattr(question, f"{field}_en", None)
        if en_val:
            return en_val
    return getattr(question, field)
def _build_question_text(question, index, total, lang):
    q_text = _localized(question, 'question', lang)
    opt_a = _localized(question, 'option_a', lang)
    opt_b = _localized(question, 'option_b', lang)
    opt_c = _localized(question, 'option_c', lang)
    opt_d = _localized(question, 'option_d', lang)
    return (
        f"📝 <b>{index}/{total}</b>\n\n"
        f"{q_text}\n\n"
        f"• <b>A</b> {opt_a}\n"
        f"• <b>B</b> {opt_b}\n"
        f"• <b>C</b> {opt_c}\n"
        f"• <b>D</b> {opt_d}"
    )
@router.callback_query(F.data.startswith("quiz:start:"))
async def quiz_start(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    progress_id = int(callback.data.split(":")[2])
    quiz_repo = QuizRepository(session)
    if await quiz_repo.has_result(progress_id):
        await callback.answer(
            i18n.get("quiz_already_done", user.language, default="Вы уже проходили квиз для этого квеста."),
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
    questions = await quiz_repo.get_questions_by_route(route_id)
    if not questions:
        from bot.keyboards.user import UserKeyboards
        await callback.message.edit_text(
            i18n.get("no_quiz_then_survey", user.language, default="Для этого маршрута пока нет квиза. Нажмите Продолжить для опроса."),
            reply_markup=UserKeyboards.post_quest_continue_survey(progress_id, user.language),
            parse_mode="HTML",
        )
        await callback.answer()
        return
    await state.set_state(QuizStates.answering)
    await state.update_data(
        quiz_progress_id=progress_id,
        quiz_route_id=route_id,
        quiz_question_ids=[q.id for q in questions],
        quiz_current=0,
        quiz_correct=0,
    )
    q = questions[0]
    text_msg = _build_question_text(q, 1, len(questions), user.language)
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    builder = InlineKeyboardBuilder()
    for opt in ['a', 'b', 'c', 'd']:
        builder.button(text=f"• {opt.upper()}", callback_data=f"quiz:ans:{opt}")
    builder.adjust(4)
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("skip_question", user.language, default="⏭ Пропустить вопрос"),
            callback_data="quiz:skip",
        )
    )
    await callback.message.edit_text(text_msg, reply_markup=builder.as_markup(), parse_mode="HTML")
    await callback.answer()
@router.callback_query(F.data.startswith("quiz:ans:"))
async def quiz_answer(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    question_ids = data.get("quiz_question_ids")
    if not question_ids:
        await callback.answer(
            i18n.get("quiz_session_expired", user.language, default="Сессия квиза истекла. Нажмите «Пройти квиз» снова."),
            show_alert=True,
        )
        return
    answer = callback.data.split(":")[2]
    current_idx = data.get("quiz_current", 0)
    correct_count = data.get("quiz_correct", 0)
    progress_id = data.get("quiz_progress_id")
    route_id = data.get("quiz_route_id")
    if progress_id is None or route_id is None:
        await callback.answer(
            i18n.get("quiz_session_expired", user.language, default="Сессия квиза истекла. Нажмите «Пройти квиз» снова."),
            show_alert=True,
        )
        return
    quiz_repo = QuizRepository(session)
    from sqlalchemy import select
    from bot.models.quiz import QuizQuestion
    if current_idx >= len(question_ids):
        await callback.answer("❌", show_alert=False)
        return
    result = await session.execute(select(QuizQuestion).where(QuizQuestion.id == question_ids[current_idx]))
    question = result.scalars().first()
    is_correct = question and question.correct_option == answer
    if is_correct:
        correct_count += 1
    next_idx = current_idx + 1
    total = len(question_ids)
    if next_idx < total:
        await state.update_data(quiz_current=next_idx, quiz_correct=correct_count)
        result2 = await session.execute(select(QuizQuestion).where(QuizQuestion.id == question_ids[next_idx]))
        next_q = result2.scalars().first()
        text_msg = _build_question_text(next_q, next_idx + 1, total, user.language)
        from aiogram.utils.keyboard import InlineKeyboardBuilder
        from aiogram.types import InlineKeyboardButton
        builder = InlineKeyboardBuilder()
        for opt in ['a', 'b', 'c', 'd']:
            builder.button(text=f"• {opt.upper()}", callback_data=f"quiz:ans:{opt}")
        builder.adjust(4)
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("skip_question", user.language, default="⏭ Пропустить вопрос"),
                callback_data="quiz:skip",
            )
        )
        feedback = "✅" if is_correct else "❌"
        await callback.answer(feedback)
        await callback.message.edit_text(text_msg, reply_markup=builder.as_markup(), parse_mode="HTML")
    else:
        await state.clear()
        settings = PlatformSettingsService(session)
        reward_per_correct = await settings.get_setting_decimal("quiz_reward_per_correct", Decimal("2"))
        total_reward = reward_per_correct * correct_count
        actual_reward = Decimal("0")
        if total_reward > 0:
            actual_reward = await award_quest_earnings(
                session, progress_id, user.id, route_id, total_reward, "quiz"
            )
        await quiz_repo.save_result(
            user_id=user.id,
            progress_id=progress_id,
            route_id=route_id,
            correct_count=correct_count,
            total_count=total,
            reward_given=actual_reward,
        )
        feedback = "✅" if is_correct else "❌"
        result_text = (
            f"🏁 <b>{i18n.get('quiz_finished', user.language, default='Квиз завершён!')}</b>\n\n"
            f"✅ {i18n.get('quiz_correct', user.language, default='Правильных ответов')}: {correct_count}/{total}\n"
        )
        if actual_reward > 0:
            result_text += f"💰 {i18n.get('quiz_reward', user.language, default='Награда')}: {int(actual_reward)} {i18n.get('currency_groshi', user.language, default='грошей')}\n"
        from bot.keyboards.user import UserKeyboards
        result_text += "\n\n" + i18n.get("after_quiz_survey_intro", user.language, default="Оцените качество квеста (опрос). Нажмите Продолжить.")
        await callback.answer(feedback)
        await callback.message.edit_text(result_text, reply_markup=UserKeyboards.post_quest_continue_survey(progress_id, user.language), parse_mode="HTML")
async def _quiz_go_next_or_finish(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext, correct_count: int, is_correct: bool):
    from bot.utils.i18n import i18n
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    from aiogram.types import InlineKeyboardButton
    from sqlalchemy import select
    from bot.models.quiz import QuizQuestion
    data = await state.get_data()
    question_ids = data.get("quiz_question_ids")
    current_idx = data.get("quiz_current", 0)
    progress_id = data.get("quiz_progress_id")
    route_id = data.get("quiz_route_id")
    quiz_repo = QuizRepository(session)
    next_idx = current_idx + 1
    total = len(question_ids)
    if next_idx < total:
        await state.update_data(quiz_current=next_idx, quiz_correct=correct_count)
        result2 = await session.execute(select(QuizQuestion).where(QuizQuestion.id == question_ids[next_idx]))
        next_q = result2.scalars().first()
        text_msg = _build_question_text(next_q, next_idx + 1, total, user.language)
        builder = InlineKeyboardBuilder()
        for opt in ['a', 'b', 'c', 'd']:
            builder.button(text=f"• {opt.upper()}", callback_data=f"quiz:ans:{opt}")
        builder.adjust(4)
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("skip_question", user.language, default="⏭ Пропустить вопрос"),
                callback_data="quiz:skip",
            )
        )
        await callback.message.edit_text(text_msg, reply_markup=builder.as_markup(), parse_mode="HTML")
    else:
        await state.clear()
        settings = PlatformSettingsService(session)
        reward_per_correct = await settings.get_setting_decimal("quiz_reward_per_correct", Decimal("2"))
        total_reward = reward_per_correct * correct_count
        actual_reward = Decimal("0")
        if total_reward > 0:
            actual_reward = await award_quest_earnings(
                session, progress_id, user.id, route_id, total_reward, "quiz"
            )
        await quiz_repo.save_result(
            user_id=user.id,
            progress_id=progress_id,
            route_id=route_id,
            correct_count=correct_count,
            total_count=total,
            reward_given=actual_reward,
        )
        result_text = (
            f"🏁 <b>{i18n.get('quiz_finished', user.language, default='Квиз завершён!')}</b>\n\n"
            f"✅ {i18n.get('quiz_correct', user.language, default='Правильных ответов')}: {correct_count}/{total}\n"
        )
        if actual_reward > 0:
            result_text += f"💰 {i18n.get('quiz_reward', user.language, default='Награда')}: {int(actual_reward)} {i18n.get('currency_groshi', user.language, default='грошей')}\n"
        result_text += "\n\n" + i18n.get("after_quiz_survey_intro", user.language, default="Оцените качество квеста (опрос). Нажмите Продолжить.")
        from bot.keyboards.user import UserKeyboards
        await callback.message.edit_text(result_text, reply_markup=UserKeyboards.post_quest_continue_survey(progress_id, user.language), parse_mode="HTML")
@router.callback_query(F.data == "quiz:skip")
async def quiz_skip(callback: CallbackQuery, session: AsyncSession, user: User, state: FSMContext):
    from bot.utils.i18n import i18n
    data = await state.get_data()
    question_ids = data.get("quiz_question_ids")
    if not question_ids:
        await callback.answer(
            i18n.get("quiz_session_expired", user.language, default="Сессия квиза истекла. Нажмите «Пройти квиз» снова."),
            show_alert=True,
        )
        return
    correct_count = data.get("quiz_correct", 0)
    await callback.answer(i18n.get("question_skipped", user.language, default="⏭ Вопрос пропущен"))
    await _quiz_go_next_or_finish(callback, session, user, state, correct_count, False)