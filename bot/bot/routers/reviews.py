import logging
from aiogram import Router, F
from aiogram.filters import Command, StateFilter
from aiogram.fsm.context import FSMContext
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.utils.keyboard import InlineKeyboardBuilder
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from sqlalchemy import select
from bot.models.user_progress import ProgressStatus
from bot.repositories.progress import ProgressRepository
from bot.repositories.review import ReviewRepository
from bot.repositories.route import RouteRepository
from bot.fsm.states import ReviewStates
from bot.services.admin_notifier import AdminNotifier
from bot.loader import bot
from bot.config import load_config
logger = logging.getLogger(__name__)
router = Router()
config = load_config()
@router.message(Command("review"))
async def cmd_review(message: Message, user: User, session: AsyncSession, state: FSMContext):
    progress_repo = ProgressRepository(session)
    review_repo = ReviewRepository(session)
    result = await session.execute(
        select(progress_repo.model)
        .where(
            progress_repo.model.user_id == user.id,
            progress_repo.model.status == ProgressStatus.COMPLETED
        )
        .order_by(progress_repo.model.completed_at.desc())
    )
    from bot.utils.i18n import i18n
    completed_progresses = list(result.scalars().all())
    if not completed_progresses:
        await message.answer(
            i18n.get("no_completed_routes", user.language) + "\n\n" +
            i18n.get("complete_quest_to_review", user.language, default="Complete at least one quest to leave a review!")
        )
        return
    progresses_without_review = []
    route_repo = RouteRepository(session)
    for progress in completed_progresses:
        existing_review = await review_repo.get_by_progress(progress.id)
        if not existing_review:
            route = await route_repo.get(progress.route_id)
            if route:
                progresses_without_review.append((progress, route))
    if not progresses_without_review:
        await message.answer(
            i18n.get("all_reviews_submitted", user.language) + "\n\n" +
            i18n.get("thanks_for_activity", user.language)
        )
        return
    from bot.utils.i18n import get_localized_field
    builder = InlineKeyboardBuilder()
    for progress, route in progresses_without_review[:10]:
        route_name = get_localized_field(route, 'name', user.language)
        builder.row(
            InlineKeyboardButton(
                text=f"⭐ {route_name}",
                callback_data=f"review:select:{progress.id}"
            )
        )
    from bot.config import load_config
    from bot.utils.i18n import i18n
    config = load_config()
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("open_reviews_page", user.language),
            url=f"{config.web.site_url}/reviews.php"
        )
    )
    await message.answer(
        f"⭐ <b>{i18n.get('leave_review_title', user.language)}</b>\n\n"
        f"{i18n.get('choose_route_review_text', user.language)}",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("review:select:"))
async def review_select_route(callback: CallbackQuery, session: AsyncSession, state: FSMContext, user: User):
    progress_id = int(callback.data.split(":")[-1])
    progress_repo = ProgressRepository(session)
    progress = await progress_repo.get(progress_id)
    if not progress:
        await callback.answer("Маршрут не найден", show_alert=True)
        return
    from bot.utils.i18n import i18n, get_localized_field
    route_repo = RouteRepository(session)
    route = await route_repo.get(progress.route_id)
    route_name = get_localized_field(route, 'name', user.language)
    await state.update_data(progress_id=progress_id, route_id=route.id, route_name=route_name)
    await state.set_state(ReviewStates.rating)
    builder = InlineKeyboardBuilder()
    for i in range(1, 6):
        star = "⭐" * i
        builder.button(text=f"{star} {i}", callback_data=f"review:rate:{i}")
    builder.adjust(5)
    await callback.message.edit_text(
        f"⭐ <b>{i18n.get('review_for_route', user.language, default='Review for route')}:</b> {route_name}\n\n"
        f"{i18n.get('rate_route', user.language)}:",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("review:rate:"), StateFilter(ReviewStates.rating))
async def review_rate(callback: CallbackQuery, state: FSMContext):
    rating = int(callback.data.split(":")[-1])
    data = await state.get_data()
    await state.update_data(rating=rating)
    await state.set_state(ReviewStates.text)
    await callback.message.edit_text(
        f"⭐ <b>Отзыв на маршрут:</b> {data['route_name']}\n"
        f"Оценка: {'⭐' * rating}\n\n"
        f"Теперь напишите текст отзыва (или отправьте /skip для пропуска):",
        parse_mode="HTML"
    )
@router.message(Command("skip"), StateFilter(ReviewStates.text))
async def review_skip_text(message: Message, user: User, session: AsyncSession, state: FSMContext):
    data = await state.get_data()
    review_repo = ReviewRepository(session)
    await review_repo.create_review(
        user_id=user.id,
        route_id=data['route_id'],
        progress_id=data['progress_id'],
        rating=data['rating'],
        text=None
    )
    await message.answer(
        "✅ Спасибо за отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}"
    )
    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
    await admin_notifier.notify_new_review(
        user_id=user.telegram_id,
        username=user.username,
        route_name=data['route_name'],
        rating=data['rating'],
        text=None
    )
    await state.clear()
@router.message(StateFilter(ReviewStates.text))
async def review_text(message: Message, user: User, session: AsyncSession, state: FSMContext):
    text = message.text
    data = await state.get_data()
    if len(text) > 1000:
        await message.answer(
            "❌ Текст отзыва слишком длинный (максимум 1000 символов).\n"
            "Попробуйте сократить."
        )
        return
    review_repo = ReviewRepository(session)
    await review_repo.create_review(
        user_id=user.id,
        route_id=data['route_id'],
        progress_id=data['progress_id'],
        rating=data['rating'],
        text=text
    )
    await message.answer(
        "✅ Спасибо за подробный отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}\n"
        f"Отзыв: {text[:100]}{'...' if len(text) > 100 else ''}"
    )
    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
    await admin_notifier.notify_new_review(
        user_id=user.telegram_id,
        username=user.username,
        route_name=data['route_name'],
        rating=data['rating'],
        text=text
    )
    await state.clear()