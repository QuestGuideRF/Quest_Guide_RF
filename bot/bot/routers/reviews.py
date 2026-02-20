import logging
<<<<<<< HEAD
from decimal import Decimal
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
from bot.repositories.token import TokenRepository
from bot.fsm.states import ReviewStates
from bot.services.admin_notifier import AdminNotifier
from bot.services.platform_settings import PlatformSettingsService
from bot.models.token_transaction import PaymentMethod
from bot.loader import bot, config
from bot.utils.i18n import i18n
logger = logging.getLogger(__name__)
def _review_done_keyboard(lang: str, progress_id: int = None) -> InlineKeyboardMarkup:
    from bot.keyboards.user import UserKeyboards
    return UserKeyboards.review_done_only_main_and_certificate(lang)
router = Router()
async def _show_review_screen(target, user: User, session: AsyncSession, edit: bool = False):
=======
from bot.fsm.states import ReviewStates
from bot.services.admin_notifier import AdminNotifier
from bot.loader import bot
from bot.config import load_config
logger = logging.getLogger(__name__)
router = Router()
config = load_config()
@router.message(Command("review"))
async def cmd_review(message: Message, user: User, session: AsyncSession, state: FSMContext):
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        text = (
            i18n.get("no_completed_routes", user.language) + "\n\n" +
            i18n.get("complete_quest_to_review", user.language, default="Complete at least one quest to leave a review!")
        )
        if edit:
            try:
                await target.edit_text(text)
            except Exception:
                await target.answer(text)
        else:
            await target.answer(text)
=======
        await message.answer(
            i18n.get("no_completed_routes", user.language) + "\n\n" +
            i18n.get("complete_quest_to_review", user.language, default="Complete at least one quest to leave a review!")
        )
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        text = (
            i18n.get("all_reviews_submitted", user.language) + "\n\n" +
            i18n.get("thanks_for_activity", user.language)
        )
        if edit:
            try:
                await target.edit_text(text)
            except Exception:
                await target.answer(text)
        else:
            await target.answer(text)
=======
        await message.answer(
            i18n.get("all_reviews_submitted", user.language) + "\n\n" +
            i18n.get("thanks_for_activity", user.language)
        )
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
=======
    from bot.config import load_config
    from bot.utils.i18n import i18n
    config = load_config()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    builder.row(
        InlineKeyboardButton(
            text=i18n.get("open_reviews_page", user.language),
            url=f"{config.web.site_url}/reviews.php"
        )
    )
<<<<<<< HEAD
    text = (
        f"⭐ <b>{i18n.get('leave_review_title', user.language)}</b>\n\n"
        f"{i18n.get('choose_route_review_text', user.language)}"
    )
    if edit:
        try:
            await target.edit_text(text, reply_markup=builder.as_markup(), parse_mode="HTML")
        except Exception:
            await target.answer(text, reply_markup=builder.as_markup(), parse_mode="HTML")
    else:
        await target.answer(text, reply_markup=builder.as_markup(), parse_mode="HTML")
@router.message(Command("review"))
async def cmd_review(message: Message, user: User, session: AsyncSession, state: FSMContext):
    await _show_review_screen(message, user, session, edit=False)
@router.callback_query(F.data == "open_review")
async def cb_open_review(callback: CallbackQuery, user: User, session: AsyncSession):
    await _show_review_screen(callback.message, user, session, edit=True)
    await callback.answer()
=======
    await message.answer(
        f"⭐ <b>{i18n.get('leave_review_title', user.language)}</b>\n\n"
        f"{i18n.get('choose_route_review_text', user.language)}",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        builder.button(text=f"{i} ⭐", callback_data=f"review:rate:{i}")
=======
        star = "⭐" * i
        builder.button(text=f"{star} {i}", callback_data=f"review:rate:{i}")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    builder.adjust(5)
    await callback.message.edit_text(
        f"⭐ <b>{i18n.get('review_for_route', user.language, default='Review for route')}:</b> {route_name}\n\n"
        f"{i18n.get('rate_route', user.language)}:",
        reply_markup=builder.as_markup(),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("review:rate:"), StateFilter(ReviewStates.rating))
<<<<<<< HEAD
async def review_rate(callback: CallbackQuery, state: FSMContext, user: User):
=======
async def review_rate(callback: CallbackQuery, state: FSMContext):
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    rating = int(callback.data.split(":")[-1])
    data = await state.get_data()
    await state.update_data(rating=rating)
    await state.set_state(ReviewStates.text)
    await callback.message.edit_text(
        f"⭐ <b>Отзыв на маршрут:</b> {data['route_name']}\n"
        f"Оценка: {'⭐' * rating}\n\n"
<<<<<<< HEAD
        f"{i18n.get('write_review', user.language, default='Напишите текст отзыва (или /skip для пропуска)')}\n\n"
        f"{i18n.get('can_skip_cmd', user.language, default='Можно пропустить: /skip')}",
        parse_mode="HTML"
    )
async def _give_review_reward(session: AsyncSession, user: User, review) -> Decimal:
    settings = PlatformSettingsService(session)
    if not await settings.get_review_reward_enabled():
        return Decimal("0")
    reward_amount = await settings.get_review_reward_amount()
    if reward_amount <= 0:
        return Decimal("0")
    token_repo = TokenRepository(session)
    await token_repo.deposit(
        user_id=user.id,
        amount=reward_amount,
        payment_method=PaymentMethod.SYSTEM,
        description=f"Бонус за отзыв на маршрут"
    )
    review.reward_given = True
    review.reward_amount = reward_amount
    await session.commit()
    return reward_amount
=======
        f"Теперь напишите текст отзыва (или отправьте /skip для пропуска):",
        parse_mode="HTML"
    )
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.message(Command("skip"), StateFilter(ReviewStates.text))
async def review_skip_text(message: Message, user: User, session: AsyncSession, state: FSMContext):
    data = await state.get_data()
    review_repo = ReviewRepository(session)
<<<<<<< HEAD
    review = await review_repo.create_review(
=======
    await review_repo.create_review(
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        user_id=user.id,
        route_id=data['route_id'],
        progress_id=data['progress_id'],
        rating=data['rating'],
        text=None
    )
<<<<<<< HEAD
    reward = await _give_review_reward(session, user, review)
    reward_text = f"\n\n💰 Вам начислено {int(reward)} грошей за отзыв!" if reward > 0 else ""
    progress_id = data.get("progress_id")
    done_text = (
        f"✅ Спасибо за отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}{reward_text}\n\n"
        f"{i18n.get('post_quest_then_quiz', user.language, default='Пройти квиз по маршруту?')}\n\n"
        f"{i18n.get('can_skip_cmd', user.language, default='Можно пропустить: /skip')}"
    )
    await message.answer(
        done_text,
        reply_markup=_review_done_keyboard(user.language, progress_id),
=======
    await message.answer(
        "✅ Спасибо за отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
    await admin_notifier.notify_new_review(
        user_id=user.telegram_id,
        username=user.username,
        route_name=data['route_name'],
        rating=data['rating'],
<<<<<<< HEAD
        text=None,
        first_name=user.first_name,
=======
        text=None
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
    review = await review_repo.create_review(
=======
    await review_repo.create_review(
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        user_id=user.id,
        route_id=data['route_id'],
        progress_id=data['progress_id'],
        rating=data['rating'],
        text=text
    )
<<<<<<< HEAD
    reward = await _give_review_reward(session, user, review)
    reward_text = f"\n\n💰 Вам начислено {int(reward)} грошей за отзыв!" if reward > 0 else ""
    progress_id = data.get("progress_id")
    done_text = (
        f"✅ Спасибо за подробный отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}\n"
        f"Отзыв: {text[:100]}{'...' if len(text) > 100 else ''}{reward_text}\n\n"
        f"{i18n.get('post_quest_then_quiz', user.language, default='Пройти квиз по маршруту?')}\n\n"
        f"{i18n.get('can_skip_cmd', user.language, default='Можно пропустить: /skip')}"
    )
    await message.answer(
        done_text,
        reply_markup=_review_done_keyboard(user.language, progress_id),
=======
    await message.answer(
        "✅ Спасибо за подробный отзыв!\n\n"
        f"Ваша оценка: {'⭐' * data['rating']}\n"
        f"Отзыв: {text[:100]}{'...' if len(text) > 100 else ''}"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
    await admin_notifier.notify_new_review(
        user_id=user.telegram_id,
        username=user.username,
        route_name=data['route_name'],
        rating=data['rating'],
<<<<<<< HEAD
        text=text,
        first_name=user.first_name,
=======
        text=text
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    await state.clear()