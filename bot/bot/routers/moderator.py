import logging
from typing import Optional
from aiogram import Router, F
from aiogram.types import Message, CallbackQuery
from aiogram.exceptions import TelegramBadRequest
from aiogram.filters import Command
from aiogram.fsm.context import FSMContext
from aiogram.fsm.state import State, StatesGroup
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User, UserRole
from bot.models.moderator_request import RequestStatus
from bot.repositories.moderator import ModeratorRepository
from bot.keyboards.moderator import ModeratorKeyboards
from bot.keyboards.user import UserKeyboards
from bot.utils.i18n import i18n
from bot.loader import config
logger = logging.getLogger(__name__)
router = Router()
class ModeratorStates(StatesGroup):
    waiting_request_message = State()
    waiting_withdraw_details = State()
    waiting_route_name = State()
    waiting_route_city = State()
    waiting_route_description = State()
    waiting_route_price = State()
    waiting_route_type = State()
    editing_route_name = State()
    editing_route_description = State()
    editing_route_price = State()
    waiting_point_name = State()
    waiting_point_location = State()
    waiting_point_fact = State()
    waiting_point_task_type = State()
    waiting_point_task = State()
    waiting_point_text_answer = State()
    editing_point_name = State()
    editing_point_fact = State()
    editing_point_task = State()
    waiting_city_name = State()
    waiting_admin_message = State()
@router.message(Command("become_creator"))
async def cmd_become_creator(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    if user.role == UserRole.MODERATOR:
        text = i18n.get("mod_menu_title", user.language)
        await message.answer(
            text,
            reply_markup=ModeratorKeyboards.main_menu(user.language),
            parse_mode="HTML"
        )
        return
    if user.role == UserRole.ADMIN:
        text = i18n.get("mod_menu_title", user.language)
        await message.answer(
            text,
            reply_markup=ModeratorKeyboards.main_menu(user.language),
            parse_mode="HTML"
        )
        return
    mod_repo = ModeratorRepository(session)
    pending_request = await mod_repo.get_pending_request(user.id)
    if pending_request:
        await message.answer(
            i18n.get("mod_request_pending", user.language),
            reply_markup=ModeratorKeyboards.request_status(user.language),
            parse_mode="HTML"
        )
        return
    requests = await mod_repo.get_user_requests(user.id)
    last_rejected = next(
        (r for r in requests if r.status == RequestStatus.REJECTED),
        None
    )
    await message.answer(
        i18n.get("mod_request_title", user.language),
        parse_mode="HTML"
    )
    await message.answer(
        i18n.get("mod_request_prompt", user.language),
        reply_markup=ModeratorKeyboards.cancel_request(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_request_message)
@router.callback_query(F.data == "become_creator")
async def cb_become_creator(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    if user.role == UserRole.MODERATOR or user.role == UserRole.ADMIN:
        text = i18n.get("mod_menu_title", user.language)
        await callback.message.edit_text(
            text,
            reply_markup=ModeratorKeyboards.main_menu(user.language),
            parse_mode="HTML"
        )
        await callback.answer()
        return
    mod_repo = ModeratorRepository(session)
    pending_request = await mod_repo.get_pending_request(user.id)
    if pending_request:
        await callback.message.edit_text(
            i18n.get("mod_request_pending", user.language),
            reply_markup=ModeratorKeyboards.request_status(user.language),
            parse_mode="HTML"
        )
        await callback.answer()
        return
    await callback.message.edit_text(
        i18n.get("mod_request_title", user.language),
        parse_mode="HTML"
    )
    await callback.message.answer(
        i18n.get("mod_request_prompt", user.language),
        reply_markup=ModeratorKeyboards.cancel_request(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_request_message)
    await callback.answer()
@router.message(ModeratorStates.waiting_request_message)
async def process_request_message(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    request_text = message.text
    if not request_text or len(request_text) < 10:
        await message.answer(
            "❌ Пожалуйста, напишите более подробное обоснование (минимум 10 символов).",
            reply_markup=ModeratorKeyboards.cancel_request(user.language)
        )
        return
    mod_repo = ModeratorRepository(session)
    request = await mod_repo.create_request(user.id, request_text)
    await state.clear()
    await message.answer(
        i18n.get("mod_request_sent", user.language),
        reply_markup=UserKeyboards.main_menu(user.language),
        parse_mode="HTML"
    )
    from bot.services.admin_notifier import AdminNotifier
    admin_notifier = AdminNotifier(message.bot, config.bot.admin_ids)
    await admin_notifier.notify_moderator_request(
        user.first_name,
        user.username,
        user.telegram_id,
        request_text,
        user_id=user.id,
        request_id=request.id,
    )
@router.callback_query(F.data == "mod:check_request")
async def cb_check_request(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    mod_repo = ModeratorRepository(session)
    requests = await mod_repo.get_user_requests(user.id)
    if not requests:
        await callback.message.edit_text(
            "📭 У вас нет активных заявок.",
            reply_markup=UserKeyboards.main_menu(user.language)
        )
        await callback.answer()
        return
    last_request = requests[0]
    if last_request.status == RequestStatus.PENDING:
        text = i18n.get("mod_request_pending", user.language)
        kb = ModeratorKeyboards.request_status(user.language)
    elif last_request.status == RequestStatus.APPROVED:
        text = i18n.get("mod_request_approved", user.language)
        kb = ModeratorKeyboards.main_menu(user.language)
    else:
        comment = last_request.admin_comment or "Причина не указана"
        text = i18n.get("mod_request_rejected", user.language).format(comment=comment)
        kb = UserKeyboards.main_menu(user.language)
    try:
        await callback.message.edit_text(text, reply_markup=kb, parse_mode="HTML")
    except TelegramBadRequest as e:
        if "message is not modified" not in str(e).lower():
            raise
    await callback.answer()
@router.callback_query(F.data == "mod:menu")
async def cb_mod_menu(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    text = i18n.get("mod_menu_title", user.language)
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.main_menu(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:my_routes")
async def cb_my_routes(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(
        select(Route).where(Route.creator_id == user.id).order_by(Route.created_at.desc())
    )
    routes = list(result.scalars().all())
    if not routes:
        await callback.message.edit_text(
            i18n.get("mod_no_routes", user.language),
            reply_markup=ModeratorKeyboards.route_list([], user.language),
            parse_mode="HTML"
        )
    else:
        text = f"📍 <b>Ваши маршруты ({len(routes)})</b>\n\nВыберите маршрут для управления:"
        await callback.message.edit_text(
            text,
            reply_markup=ModeratorKeyboards.route_list(routes, user.language),
            parse_mode="HTML"
        )
    await callback.answer()
@router.callback_query(F.data == "mod:balance")
async def cb_mod_balance(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    mod_repo = ModeratorRepository(session)
    stats = await mod_repo.get_moderator_stats(user.id)
    text = i18n.get("mod_balance_title", user.language).format(
        balance=f"{stats['balance']:.0f}",
        total_earned=f"{stats['total_earned']:.0f}",
        total_withdrawn=f"{stats['total_withdrawn']:.0f}",
        routes=stats['total_routes'],
        sales=stats['total_sales']
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.balance_menu(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:stats")
async def cb_mod_stats(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    mod_repo = ModeratorRepository(session)
    stats = await mod_repo.get_moderator_stats(user.id)
    text = i18n.get("mod_stats_title", user.language).format(
        total_sales=stats['total_sales'],
        total_earned=f"{stats['total_earned']:.0f}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.back_to_mod_menu(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:create_route")
async def cb_create_route(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    await state.clear()
    await callback.message.edit_text(
        "📝 <b>Создание нового маршрута</b>\n\n"
        "💡 <i>Совет: создавать маршруты удобнее через веб-интерфейс (/web)</i>\n\n"
        "Шаг 1/5: Введите название маршрута:",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_name)
    await callback.answer()
@router.message(ModeratorStates.waiting_route_name)
async def process_route_name(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    name = message.text
    if not name or len(name) < 3:
        await message.answer(
            "❌ Название слишком короткое (минимум 3 символа).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    if len(name) > 100:
        await message.answer(
            "❌ Название слишком длинное (максимум 100 символов).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    from sqlalchemy import select
    from bot.models.route import Route
    existing = await session.execute(
        select(Route).where(Route.name == name)
    )
    if existing.scalar_one_or_none():
        await message.answer(
            "❌ Маршрут с таким названием уже существует. Выберите другое название.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    await state.update_data(route_name=name)
    from bot.models.city import City
    result = await session.execute(
        select(City).where(City.is_active == True).order_by(City.name)
    )
    cities = list(result.scalars().all())
    if not cities:
        await message.answer(
            "❌ Нет доступных городов. Обратитесь к администратору.",
            reply_markup=ModeratorKeyboards.back_to_mod_menu(user.language)
        )
        await state.clear()
        return
    await message.answer(
        "🏙 <b>Шаг 2/5: Выберите город:</b>",
        reply_markup=ModeratorKeyboards.city_selection(cities, user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_city)
@router.callback_query(F.data.startswith("mod:select_city:"), ModeratorStates.waiting_route_city)
async def process_route_city(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    city_id = int(callback.data.split(":")[2])
    await state.update_data(city_id=city_id)
    await callback.message.edit_text(
        "📄 <b>Шаг 3/5: Введите описание маршрута:</b>\n\n"
        "Опишите, что увидит пользователь, какие места посетит.\n"
        "(Можно пропустить)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_description)
    await callback.answer()
@router.callback_query(F.data == "mod:skip_step", ModeratorStates.waiting_route_description)
async def skip_description(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(description=None)
    await callback.message.edit_text(
        "💰 <b>Шаг 4/5: Введите цену маршрута в рублях:</b>\n\n"
        "Например: 299",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_price)
    await callback.answer()
@router.message(ModeratorStates.waiting_route_description)
async def process_route_description(
    message: Message,
    user: User,
    state: FSMContext,
):
    description = message.text
    if description and len(description) > 2000:
        await message.answer(
            "❌ Описание слишком длинное (максимум 2000 символов).",
            reply_markup=ModeratorKeyboards.skip_or_cancel(user.language)
        )
        return
    await state.update_data(description=description)
    await message.answer(
        "💰 <b>Шаг 4/5: Введите цену маршрута в рублях:</b>\n\n"
        "Например: 299",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_price)
@router.message(ModeratorStates.waiting_route_price)
async def process_route_price(
    message: Message,
    user: User,
    state: FSMContext,
):
    try:
        price = int(message.text.strip())
        if price < 0 or price > 100000:
            raise ValueError("Price out of range")
    except (ValueError, AttributeError):
        await message.answer(
            "❌ Введите корректную цену (от 0 до 100000 грошей).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    await state.update_data(price=price)
    await message.answer(
        "🚶 <b>Шаг 5/5: Выберите тип маршрута:</b>",
        reply_markup=ModeratorKeyboards.route_type_selection(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_route_type)
@router.callback_query(F.data.startswith("mod:route_type:"), ModeratorStates.waiting_route_type)
async def process_route_type(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    route_type = callback.data.split(":")[2]
    data = await state.get_data()
    from sqlalchemy import select
    from bot.models.city import City
    result = await session.execute(select(City).where(City.id == data['city_id']))
    city = result.scalar_one_or_none()
    city_name = city.name if city else "Неизвестный город"
    type_text = "🚶 Пешеходный" if route_type == "walking" else "🚴 Велосипедный"
    desc_text = data.get('description') or "Не указано"
    if len(desc_text) > 100:
        desc_text = desc_text[:100] + "..."
    summary = (
        f"📋 <b>Проверьте данные маршрута:</b>\n\n"
        f"📝 Название: {data['route_name']}\n"
        f"🏙 Город: {city_name}\n"
        f"📄 Описание: {desc_text}\n"
        f"💰 Цена: {data['price']} грошей\n"
        f"🚶 Тип: {type_text}\n\n"
        f"Создать маршрут?"
    )
    await state.update_data(route_type=route_type)
    await callback.message.edit_text(
        summary,
        reply_markup=ModeratorKeyboards.confirm_route_creation(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:confirm_create_route")
async def confirm_create_route(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    if not data.get('route_name') or not data.get('city_id'):
        await callback.answer("❌ Данные утеряны, начните заново", show_alert=True)
        await state.clear()
        return
    from bot.models.route import Route, RouteType
    route = Route(
        name=data['route_name'],
        city_id=data['city_id'],
        description=data.get('description'),
        price=data.get('price', 299),
        route_type=RouteType.WALKING if data.get('route_type') == 'walking' else RouteType.CYCLING,
        creator_id=user.id,
        is_active=False,
        is_published=False,
        difficulty=2,
        estimated_duration=60,
        max_hints_per_route=3,
    )
    session.add(route)
    await session.commit()
    await session.refresh(route)
    await state.clear()
    await callback.message.edit_text(
        f"✅ <b>Маршрут «{route.name}» создан!</b>\n\n"
        f"📍 Теперь добавьте точки маршрута.\n"
        f"После добавления точек можно опубликовать маршрут.\n\n"
        f"⏳ Маршрут будет доступен после проверки администратором.",
        reply_markup=ModeratorKeyboards.route_created_actions(route.id, user.language),
        parse_mode="HTML"
    )
    await callback.answer("✅ Маршрут создан!")
    from bot.keyboards.admin import route_moderation_actions
    for admin_id in config.bot.admin_ids:
        try:
            admin_text = (
                f"🆕 <b>Модератор создал новый маршрут</b>\n\n"
                f"👤 Модератор: {user.first_name or ''} (@{user.username or 'no_username'})\n"
                f"📍 Маршрут: {route.name}\n"
                f"💰 Цена: {route.price} грошей\n\n"
                f"Маршрут ожидает проверки и публикации."
            )
            await callback.bot.send_message(
                admin_id,
                admin_text,
                parse_mode="HTML",
                reply_markup=route_moderation_actions(route.id, user.id)
            )
        except Exception as e:
            logger.error(f"Failed to notify admin {admin_id}: {e}")
@router.callback_query(F.data == "mod:cancel_create")
async def cancel_create(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.clear()
    await callback.message.edit_text(
        i18n.get("mod_menu_title", user.language),
        reply_markup=ModeratorKeyboards.main_menu(user.language),
        parse_mode="HTML"
    )
    await callback.answer("Создание отменено")
@router.callback_query(F.data == "mod:withdraw")
async def cb_withdraw(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    mod_repo = ModeratorRepository(session)
    balance = await mod_repo.get_or_create_balance(user.id)
    text = i18n.get("mod_withdraw_prompt", user.language).format(
        balance=f"{balance.balance:.0f}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.back_to_mod_menu(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_withdraw_details)
    await callback.answer()
@router.message(ModeratorStates.waiting_withdraw_details)
async def process_withdraw(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    text = message.text
    if not text or "|" not in text:
        await message.answer(
            i18n.get("mod_withdraw_error", user.language),
            reply_markup=ModeratorKeyboards.balance_menu(user.language)
        )
        await state.clear()
        return
    try:
        parts = text.split("|", 1)
        amount = int(parts[0].strip())
        details = parts[1].strip()
        if amount < 100:
            await message.answer("❌ Минимальная сумма вывода: 100 грошей")
            return
        mod_repo = ModeratorRepository(session)
        from decimal import Decimal
        request = await mod_repo.create_withdrawal_request(
            user.id,
            Decimal(str(amount)),
            details
        )
        if not request:
            await message.answer(
                i18n.get("mod_withdraw_error", user.language),
                reply_markup=ModeratorKeyboards.balance_menu(user.language)
            )
            await state.clear()
            return
        await state.clear()
        await message.answer(
            i18n.get("mod_withdraw_success", user.language).format(
                amount=amount,
                details=details
            ),
            reply_markup=ModeratorKeyboards.balance_menu(user.language),
            parse_mode="HTML"
        )
        from bot.services.admin_notifier import AdminNotifier
        admin_notifier = AdminNotifier(message.bot, config.bot.admin_ids)
        for admin_id in config.bot.admin_ids:
            try:
                admin_text = (
                    f"💸 <b>Новая заявка на вывод</b>\n\n"
                    f"👤 От: {user.first_name or ''} (@{user.username or 'no_username'})\n"
                    f"💰 Сумма: {amount} грошей\n"
                    f"📝 Реквизиты: {details}"
                )
                await message.bot.send_message(admin_id, admin_text, parse_mode="HTML")
            except Exception as e:
                logger.error(f"Failed to notify admin {admin_id}: {e}")
    except ValueError:
        await message.answer(
            i18n.get("mod_withdraw_error", user.language),
            reply_markup=ModeratorKeyboards.balance_menu(user.language)
        )
        await state.clear()
@router.callback_query(F.data == "mod:transactions")
async def cb_transactions(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    mod_repo = ModeratorRepository(session)
    transactions = await mod_repo.get_transactions(user.id, limit=10)
    if not transactions:
        text = "📜 История операций пуста."
    else:
        text = "📜 <b>Последние операции:</b>\n\n"
        for tx in transactions:
            type_emoji = "📈" if tx.type.value == "earning" else "📉"
            sign = "+" if tx.type.value == "earning" else "-"
            text += f"{type_emoji} {sign}{tx.amount:.0f} грошей - {tx.description or tx.type.value}\n"
            text += f"   <i>{tx.created_at.strftime('%d.%m.%Y %H:%M')}</i>\n\n"
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.balance_menu(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:route:"))
async def cb_route_details(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав модератора", show_alert=True)
        return
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from sqlalchemy.orm import selectinload
    from bot.models.route import Route
    result = await session.execute(
        select(Route).options(selectinload(Route.points)).where(Route.id == route_id)
    )
    route = result.scalar_one_or_none()
    if not route:
        await callback.answer("❌ Маршрут не найден", show_alert=True)
        return
    if route.creator_id != user.id and user.role != UserRole.ADMIN:
        await callback.answer("❌ Это не ваш маршрут", show_alert=True)
        return
    status = "✅ Опубликован" if route.is_published else "⏸ Не опубликован"
    type_text = "🚶 Пешеходный" if route.route_type.value == "walking" else "🚴 Велосипедный"
    desc_text = route.description or "Не указано"
    if len(desc_text) > 150:
        desc_text = desc_text[:150] + "..."
    text = (
        f"📍 <b>{route.name}</b>\n\n"
        f"📊 Статус: {status}\n"
        f"💰 Цена: {route.price} грошей\n"
        f"🚶 Тип: {type_text}\n"
        f"📍 Точек: {len(route.points)}\n\n"
        f"📄 Описание:\n{desc_text}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.route_actions(route_id, route.is_published, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:publish:"))
async def cb_publish_route(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from sqlalchemy.orm import selectinload
    from bot.models.route import Route
    result = await session.execute(
        select(Route).options(selectinload(Route.points)).where(Route.id == route_id)
    )
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Маршрут не найден или нет доступа", show_alert=True)
        return
    if len(route.points) == 0:
        await callback.answer("❌ Добавьте хотя бы одну точку перед публикацией", show_alert=True)
        return
    route.is_published = True
    route.is_active = True
    await session.commit()
    await callback.answer("✅ Маршрут опубликован!", show_alert=True)
    await cb_route_details(callback, session, user)
@router.callback_query(F.data.startswith("mod:unpublish:"))
async def cb_unpublish_route(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Маршрут не найден или нет доступа", show_alert=True)
        return
    route.is_published = False
    await session.commit()
    await callback.answer("⏸ Маршрут снят с публикации", show_alert=True)
    await cb_route_details(callback, session, user)
@router.callback_query(F.data.startswith("mod:delete_route:"))
async def cb_delete_route_confirm(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Маршрут не найден или нет доступа", show_alert=True)
        return
    await callback.message.edit_text(
        f"❓ Удалить маршрут «{route.name}»?\n\nВсе точки маршрута также будут удалены. Это действие нельзя отменить.",
        reply_markup=ModeratorKeyboards.confirm_delete("route", route_id, f"mod:route:{route_id}", user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:confirm_delete:route:"))
async def cb_confirm_delete_route(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[3])
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Маршрут не найден или нет доступа", show_alert=True)
        return
    await session.delete(route)
    await session.commit()
    await callback.answer("✅ Маршрут удалён", show_alert=True)
    result = await session.execute(
        select(Route).where(Route.creator_id == user.id).order_by(Route.created_at.desc())
    )
    routes = list(result.scalars().all())
    if not routes:
        await callback.message.edit_text(
            i18n.get("mod_no_routes", user.language),
            reply_markup=ModeratorKeyboards.route_list([], user.language),
            parse_mode="HTML"
        )
    else:
        text = f"📍 <b>Ваши маршруты ({len(routes)})</b>\n\nМаршрут удалён. Выберите маршрут для управления:"
        await callback.message.edit_text(
            text,
            reply_markup=ModeratorKeyboards.route_list(routes, user.language),
            parse_mode="HTML"
        )
@router.callback_query(F.data.startswith("mod:edit_route:"))
async def cb_edit_route_menu(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    await callback.message.edit_text(
        "✏️ <b>Редактирование маршрута</b>\n\nВыберите, что хотите изменить:",
        reply_markup=ModeratorKeyboards.edit_route_menu(route_id, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:edit_name:"))
async def cb_edit_route_name(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    await state.update_data(editing_route_id=route_id)
    await callback.message.edit_text(
        "📝 Введите новое название маршрута:",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.editing_route_name)
    await callback.answer()
@router.message(ModeratorStates.editing_route_name)
async def process_edit_route_name(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    route_id = data.get('editing_route_id')
    if not route_id:
        await state.clear()
        return
    name = message.text
    if not name or len(name) < 3 or len(name) > 100:
        await message.answer(
            "❌ Название должно быть от 3 до 100 символов.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if route and (route.creator_id == user.id or user.role == UserRole.ADMIN):
        route.name = name
        await session.commit()
        await message.answer(f"✅ Название изменено на «{name}»")
    await state.clear()
    from aiogram.types import CallbackQuery as CQ
    fake_callback_data = f"mod:route:{route_id}"
    await message.answer(
        i18n.get("mod_menu_title", user.language),
        reply_markup=ModeratorKeyboards.route_actions(route_id, route.is_published if route else False, user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("mod:edit_desc:"))
async def cb_edit_route_desc(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    await state.update_data(editing_route_id=route_id)
    await callback.message.edit_text(
        "📄 Введите новое описание маршрута:",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.editing_route_description)
    await callback.answer()
@router.message(ModeratorStates.editing_route_description)
async def process_edit_route_desc(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    route_id = data.get('editing_route_id')
    if not route_id:
        await state.clear()
        return
    desc = message.text
    if desc and len(desc) > 2000:
        await message.answer(
            "❌ Описание слишком длинное (максимум 2000 символов).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if route and (route.creator_id == user.id or user.role == UserRole.ADMIN):
        route.description = desc
        await session.commit()
        await message.answer("✅ Описание обновлено")
    await state.clear()
    await message.answer(
        "Выберите действие:",
        reply_markup=ModeratorKeyboards.route_actions(route_id, route.is_published if route else False, user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("mod:edit_price:"))
async def cb_edit_route_price(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    await state.update_data(editing_route_id=route_id)
    await callback.message.edit_text(
        "💰 Введите новую цену маршрута в рублях:",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.editing_route_price)
    await callback.answer()
@router.message(ModeratorStates.editing_route_price)
async def process_edit_route_price(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    route_id = data.get('editing_route_id')
    if not route_id:
        await state.clear()
        return
    try:
        price = int(message.text.strip())
        if price < 0 or price > 100000:
            raise ValueError()
    except Exception as e:
        logger.warning("moderator: неверная цена '%s': %s", message.text, e)
        await message.answer(
            "❌ Введите корректную цену (от 0 до 100000).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if route and (route.creator_id == user.id or user.role == UserRole.ADMIN):
        route.price = price
        await session.commit()
        await message.answer(f"✅ Цена изменена на {price} грошей")
    await state.clear()
    await message.answer(
        "Выберите действие:",
        reply_markup=ModeratorKeyboards.route_actions(route_id, route.is_published if route else False, user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("mod:route_stats:"))
async def cb_route_stats(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select, func
    from bot.models.route import Route
    from bot.models.user_progress import UserProgress
    result = await session.execute(select(Route).where(Route.id == route_id))
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Нет доступа", show_alert=True)
        return
    completions = await session.execute(
        select(func.count()).select_from(UserProgress).where(
            UserProgress.route_id == route_id,
            UserProgress.status == 'COMPLETED'
        )
    )
    completions_count = completions.scalar() or 0
    in_progress = await session.execute(
        select(func.count()).select_from(UserProgress).where(
            UserProgress.route_id == route_id,
            UserProgress.status == 'IN_PROGRESS'
        )
    )
    in_progress_count = in_progress.scalar() or 0
    text = (
        f"📊 <b>Статистика маршрута «{route.name}»</b>\n\n"
        f"✅ Завершили: {completions_count}\n"
        f"🚶 В процессе: {in_progress_count}\n"
        f"💰 Цена: {route.price} грошей\n"
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.route_actions(route_id, route.is_published, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:points:"))
async def cb_route_points(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    route_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from sqlalchemy.orm import selectinload
    from bot.models.route import Route
    result = await session.execute(
        select(Route).options(selectinload(Route.points)).where(Route.id == route_id)
    )
    route = result.scalar_one_or_none()
    if not route or (route.creator_id != user.id and user.role != UserRole.ADMIN):
        await callback.answer("❌ Нет доступа", show_alert=True)
        return
    points = sorted(route.points, key=lambda p: p.order)
    if not points:
        text = f"📍 <b>Точки маршрута «{route.name}»</b>\n\nТочек пока нет. Добавьте первую точку!"
    else:
        text = f"📍 <b>Точки маршрута «{route.name}»</b>\n\nВсего точек: {len(points)}"
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.points_list(points, route_id, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:add_point:"))
async def cb_add_point(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    await state.update_data(adding_point_to_route=route_id)
    await callback.message.edit_text(
        "📍 <b>Добавление новой точки</b>\n\n"
        "Шаг 1/4: Введите название точки (места):",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_name)
    await callback.answer()
@router.message(ModeratorStates.waiting_point_name)
async def process_point_name(
    message: Message,
    user: User,
    state: FSMContext,
):
    name = message.text
    if not name or len(name) < 2:
        await message.answer(
            "❌ Название слишком короткое.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    await state.update_data(point_name=name)
    await message.answer(
        "📍 <b>Шаг 2/4: Отправьте геолокацию точки</b>\n\n"
        "Нажмите на 📎 и выберите «Геопозиция» или введите координаты в формате:\n"
        "<code>55.751244, 37.618423</code>\n\n"
        "(Можно пропустить)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_location)
@router.message(ModeratorStates.waiting_point_location, F.location)
async def process_point_location(
    message: Message,
    user: User,
    state: FSMContext,
):
    await state.update_data(
        point_lat=message.location.latitude,
        point_lon=message.location.longitude
    )
    await message.answer(
        "📝 <b>Шаг 3/4: Введите интересный факт о месте</b>\n\n"
        "Этот текст увидит пользователь после посещения точки.\n"
        "(Можно пропустить)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_fact)
@router.message(ModeratorStates.waiting_point_location)
async def process_point_location_text(
    message: Message,
    user: User,
    state: FSMContext,
):
    text = message.text
    if text:
        try:
            parts = text.replace(",", " ").split()
            lat = float(parts[0])
            lon = float(parts[1])
            await state.update_data(point_lat=lat, point_lon=lon)
        except Exception as e:
            logger.warning("moderator: неверный формат координат '%s': %s", text, e)
            await message.answer(
                "❌ Неверный формат координат. Используйте формат: 55.751244, 37.618423",
                reply_markup=ModeratorKeyboards.skip_or_cancel(user.language)
            )
            return
    await message.answer(
        "📝 <b>Шаг 3/4: Введите интересный факт о месте</b>\n\n"
        "Этот текст увидит пользователь после посещения точки.\n"
        "(Можно пропустить)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_fact)
@router.callback_query(F.data == "mod:skip_step", ModeratorStates.waiting_point_location)
async def skip_point_location(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(point_lat=None, point_lon=None)
    await callback.message.edit_text(
        "📝 <b>Шаг 3/4: Введите интересный факт о месте</b>\n\n"
        "Этот текст увидит пользователь после посещения точки.\n"
        "(Можно пропустить)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_fact)
    await callback.answer()
@router.message(ModeratorStates.waiting_point_fact)
async def process_point_fact(
    message: Message,
    user: User,
    state: FSMContext,
):
    fact = message.text
    await state.update_data(point_fact=fact)
    await message.answer(
        "📸 <b>Шаг 4/4: Тип задания</b>\n\n"
        "Выберите, что пользователь должен сделать на этой точке:\n"
        "• <b>Фото</b> — сфотографироваться (можно указать текст задания или пропустить)\n"
        "• <b>Ввести текст</b> — вопрос или загадка, пользователь вводит ответ",
        reply_markup=ModeratorKeyboards.task_type_selection(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_task_type)
@router.callback_query(F.data == "mod:skip_step", ModeratorStates.waiting_point_fact)
async def skip_point_fact(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(point_fact=None)
    await callback.message.edit_text(
        "📸 <b>Шаг 4/4: Тип задания</b>\n\n"
        "Выберите: Фото или Ввести текст.",
        reply_markup=ModeratorKeyboards.task_type_selection(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_point_task_type)
    await callback.answer()
@router.callback_query(F.data == "mod:task_type:photo", ModeratorStates.waiting_point_task_type)
async def cb_task_type_photo(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(point_task_type="photo")
    await state.set_state(ModeratorStates.waiting_point_task)
    await callback.message.edit_text(
        "📸 <b>Текст задания (фото)</b>\n\n"
        "Что пользователь должен сделать? Например: «Сделайте фото на фоне памятника»\n"
        "(Можно пропустить — будет «Сделайте фото»)",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:task_type:text", ModeratorStates.waiting_point_task_type)
async def cb_task_type_text(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(point_task_type="text")
    await state.set_state(ModeratorStates.waiting_point_task)
    await callback.message.edit_text(
        "📝 <b>Текст задания (вопрос/загадка)</b>\n\n"
        "Введите вопрос или загадку, на которую пользователь должен ответить текстом.\n"
        "Например: «В каком году основан этот памятник?»",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "mod:skip_step", ModeratorStates.waiting_point_task_type)
async def skip_point_task_type(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    await state.update_data(point_task_type="photo")
    await state.set_state(ModeratorStates.waiting_point_task)
    await callback.message.edit_text(
        "📸 <b>Текст задания (фото)</b>\n\n"
        "Можно пропустить — будет «Сделайте фото».",
        reply_markup=ModeratorKeyboards.skip_or_cancel(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.message(ModeratorStates.waiting_point_task)
async def process_point_task(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    task_type = data.get("point_task_type") or "photo"
    task_text = (message.text or "").strip() or ("📸 Сделайте фото на этой точке" if task_type == "photo" else None)
    if task_type == "text" and not task_text:
        await message.answer("❌ Введите текст вопроса или загадки.", reply_markup=ModeratorKeyboards.cancel_only(user.language))
        return
    if task_type == "photo":
        await save_point(message, session, user, state, task_text or "📸 Сделайте фото на этой точке", task_type="photo", text_answer=None)
        return
    await state.update_data(point_task_text=task_text)
    await state.set_state(ModeratorStates.waiting_point_text_answer)
    await message.answer(
        "📝 <b>Правильный ответ</b>\n\n"
        "Введите ответ, который пользователь должен ввести (с учётом опечаток можно принять похожий вариант).",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data == "mod:skip_step", ModeratorStates.waiting_point_task)
async def skip_point_task(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await save_point(callback.message, session, user, state, "📸 Сделайте фото на этой точке", task_type="photo", text_answer=None)
    await callback.answer()
@router.message(ModeratorStates.waiting_point_text_answer)
async def process_point_text_answer(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data = await state.get_data()
    task_text = data.get("point_task_text") or "Ответьте на вопрос"
    text_answer = (message.text or "").strip()
    if not text_answer:
        await message.answer("❌ Введите правильный ответ.", reply_markup=ModeratorKeyboards.cancel_only(user.language))
        return
    await save_point(message, session, user, state, task_text, task_type="text", text_answer=text_answer)
async def save_point(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
    task_text: str,
    task_type: str = "photo",
    text_answer: Optional[str] = None,
):
    data = await state.get_data()
    route_id = data.get('adding_point_to_route')
    if not route_id:
        await state.clear()
        return
    from sqlalchemy import select, func
    from bot.models.point import Point
    from bot.models.task import Task
    max_order = await session.execute(
        select(func.max(Point.order)).where(Point.route_id == route_id)
    )
    current_max = max_order.scalar() or 0
    new_order = current_max + 1
    point = Point(
        route_id=route_id,
        name=data.get('point_name', 'Новая точка'),
        latitude=data.get('point_lat'),
        longitude=data.get('point_lon'),
        fact_text=data.get('point_fact'),
        order=new_order,
        task_type=task_type,
        accept_partial_match=(task_type == "text"),
        max_attempts=3,
        text_answer=text_answer if task_type == "text" else None,
        text_answer_hint=None,
    )
    session.add(point)
    await session.flush()
    task = Task(
        point_id=point.id,
        task_text=task_text,
        task_type=task_type,
        order=1,
    )
    session.add(task)
    await session.commit()
    await state.clear()
    from bot.models.route import Route
    route_result = await session.execute(
        select(Route).where(Route.id == route_id)
    )
    route = route_result.scalar_one_or_none()
    route_name = route.name if route else "Неизвестный"
    await message.answer(
        f"✅ Точка «{point.name}» добавлена!\n\n"
        f"Порядковый номер: {new_order}",
        reply_markup=ModeratorKeyboards.points_list([], route_id, user.language),
        parse_mode="HTML"
    )
    for admin_id in config.bot.admin_ids:
        try:
            admin_text = (
                f"📍 <b>Модератор добавил точку</b>\n\n"
                f"👤 Модератор: {user.first_name or ''} (@{user.username or 'no_username'})\n"
                f"🗺 Маршрут: {route_name}\n"
                f"📍 Точка: {point.name} (#{new_order})"
            )
            await message.bot.send_message(admin_id, admin_text, parse_mode="HTML")
        except Exception as e:
            logger.error(f"Failed to notify admin {admin_id}: {e}")
@router.callback_query(F.data.startswith("mod:point:"))
async def cb_point_details(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    point_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from sqlalchemy.orm import selectinload
    from bot.models.point import Point
    result = await session.execute(
        select(Point).options(selectinload(Point.route), selectinload(Point.tasks)).where(Point.id == point_id)
    )
    point = result.scalar_one_or_none()
    if not point:
        await callback.answer("❌ Точка не найдена", show_alert=True)
        return
    if point.route.creator_id != user.id and user.role != UserRole.ADMIN:
        await callback.answer("❌ Нет доступа", show_alert=True)
        return
    location_text = f"📍 {point.latitude}, {point.longitude}" if point.latitude else "📍 Не указано"
    fact_text = point.fact_text[:100] + "..." if point.fact_text and len(point.fact_text) > 100 else (point.fact_text or "Не указано")
    task_text = point.tasks[0].task_text if point.tasks else "Не указано"
    text = (
        f"📍 <b>{point.name}</b>\n\n"
        f"🔢 Порядок: {point.order}\n"
        f"{location_text}\n\n"
        f"📝 Факт: {fact_text}\n\n"
        f"📸 Задание: {task_text}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=ModeratorKeyboards.point_actions(point_id, point.route_id, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:delete_point:"))
async def cb_delete_point_confirm(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    point_id = int(callback.data.split(":")[2])
    from sqlalchemy import select
    from bot.models.point import Point
    result = await session.execute(select(Point).where(Point.id == point_id))
    point = result.scalar_one_or_none()
    if not point:
        await callback.answer("❌ Точка не найдена", show_alert=True)
        return
    await callback.message.edit_text(
        f"❓ Удалить точку «{point.name}»?",
        reply_markup=ModeratorKeyboards.confirm_delete("point", point_id, f"mod:points:{point.route_id}", user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("mod:confirm_delete:point:"))
async def cb_confirm_delete_point(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
):
    point_id = int(callback.data.split(":")[3])
    from sqlalchemy import select
    from bot.models.point import Point
    result = await session.execute(select(Point).where(Point.id == point_id))
    point = result.scalar_one_or_none()
    if point:
        route_id = point.route_id
        await session.delete(point)
        await session.commit()
        await callback.answer("✅ Точка удалена", show_alert=True)
        callback.data = f"mod:points:{route_id}"
        await cb_route_points(callback, session, user)
    else:
        await callback.answer("❌ Точка не найдена", show_alert=True)
@router.callback_query(F.data == "mod:create_city")
async def cb_create_city(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав", show_alert=True)
        return
    await callback.message.edit_text(
        "🏙 <b>Создание нового города</b>\n\n"
        "Введите название города:",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_city_name)
    await callback.answer()
@router.message(ModeratorStates.waiting_city_name)
async def process_city_name(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    name = message.text
    if not name or len(name) < 2:
        await message.answer(
            "❌ Название слишком короткое.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    if len(name) > 100:
        await message.answer(
            "❌ Название слишком длинное (максимум 100 символов).",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    from sqlalchemy import select
    from bot.models.city import City
    existing = await session.execute(
        select(City).where(City.name == name)
    )
    if existing.scalar_one_or_none():
        await message.answer(
            "❌ Город с таким названием уже существует.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    city = City(
        name=name,
        is_active=True,
        creator_id=user.id if user.role == UserRole.MODERATOR else None,
    )
    session.add(city)
    await session.commit()
    await state.clear()
    await message.answer(
        f"✅ Город «{name}» создан!\n\n"
        f"Теперь можно создавать маршруты в этом городе.",
        reply_markup=ModeratorKeyboards.main_menu(user.language),
        parse_mode="HTML"
    )
    for admin_id in config.bot.admin_ids:
        try:
            admin_text = (
                f"🏙 <b>Модератор создал новый город</b>\n\n"
                f"👤 Модератор: {user.first_name or ''} (@{user.username or 'no_username'})\n"
                f"🏙 Город: {name}"
            )
            await message.bot.send_message(admin_id, admin_text, parse_mode="HTML")
        except Exception as e:
            logger.error(f"Failed to notify admin {admin_id}: {e}")
@router.callback_query(F.data == "mod:contact_admin")
async def cb_contact_admin(
    callback: CallbackQuery,
    user: User,
    state: FSMContext,
):
    if user.role not in [UserRole.MODERATOR, UserRole.ADMIN]:
        await callback.answer("❌ У вас нет прав", show_alert=True)
        return
    await callback.message.edit_text(
        "📩 <b>Связь с администрацией</b>\n\n"
        "Напишите ваше сообщение администраторам.\n"
        "Они получат его и ответят вам в ближайшее время.",
        reply_markup=ModeratorKeyboards.cancel_only(user.language),
        parse_mode="HTML"
    )
    await state.set_state(ModeratorStates.waiting_admin_message)
    await callback.answer()
@router.message(ModeratorStates.waiting_admin_message)
async def process_admin_message(
    message: Message,
    user: User,
    state: FSMContext,
):
    text = message.text
    if not text or len(text) < 5:
        await message.answer(
            "❌ Сообщение слишком короткое.",
            reply_markup=ModeratorKeyboards.cancel_only(user.language)
        )
        return
    await state.clear()
    from bot.keyboards.admin import reply_to_moderator
    sent_count = 0
    for admin_id in config.bot.admin_ids:
        try:
            admin_text = (
                f"📩 <b>Сообщение от модератора</b>\n\n"
                f"👤 От: {user.first_name or ''} (@{user.username or 'no_username'})\n"
                f"🆔 ID: {user.telegram_id}\n\n"
                f"💬 Сообщение:\n{text}"
            )
            await message.bot.send_message(
                admin_id,
                admin_text,
                parse_mode="HTML",
                reply_markup=reply_to_moderator(user.telegram_id)
            )
            sent_count += 1
        except Exception as e:
            logger.error(f"Failed to send message to admin {admin_id}: {e}")
    if sent_count > 0:
        await message.answer(
            "✅ Сообщение отправлено администраторам!\n\n"
            "Ожидайте ответа.",
            reply_markup=ModeratorKeyboards.main_menu(user.language),
            parse_mode="HTML"
        )
    else:
        await message.answer(
            "❌ Не удалось отправить сообщение. Попробуйте позже.",
            reply_markup=ModeratorKeyboards.main_menu(user.language),
            parse_mode="HTML"
        )