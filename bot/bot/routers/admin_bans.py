import logging
from datetime import datetime, timedelta
from aiogram import Router, F
from aiogram.types import CallbackQuery
from aiogram.fsm.context import FSMContext
from aiogram.fsm.state import State, StatesGroup
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, func
from bot.models.user import User
from bot.keyboards.admin import bans_menu, ban_duration_menu, back_to_bans_menu
from bot.utils.safe_edit import safe_edit_text
from bot.services.admin_notifier import AdminNotifier
logger = logging.getLogger(__name__)
router = Router()
class BanStates(StatesGroup):
    waiting_for_user_id = State()
    waiting_for_ban_reason = State()
    waiting_for_unban_user_id = State()
@router.callback_query(F.data == "admin:bans")
async def show_bans_menu(callback: CallbackQuery, session: AsyncSession):
    result = await session.execute(
        select(func.count(User.id)).where(
            User.is_banned == True
        )
    )
    banned_count = result.scalar() or 0
    result = await session.execute(
        select(func.count(User.id)).where(
            User.ban_until.isnot(None),
            User.ban_until > datetime.now()
        )
    )
    temp_banned_count = result.scalar() or 0
    msg_text = (
        f"🚫 <b>Управление блокировками</b>\n\n"
        f"📊 Статистика:\n"
        f"├ Заблокировано навсегда: {banned_count}\n"
        f"└ Временно заблокировано: {temp_banned_count}\n\n"
        f"Выберите действие:"
    )
    await safe_edit_text(
        callback,
        msg_text,
        reply_markup=bans_menu()
    )
@router.callback_query(F.data == "admin:bans:search")
async def show_users_to_ban(callback: CallbackQuery, session: AsyncSession):
    result = await session.execute(
        select(User).where(
            User.role != "ADMIN",
            User.is_banned == False,
            (User.ban_until.is_(None) | (User.ban_until <= datetime.now()))
        ).order_by(User.created_at.desc()).limit(20)
    )
    users = result.scalars().all()
    if not users:
        await callback.answer("Нет доступных пользователей для блокировки", show_alert=True)
        return
    from aiogram.types import InlineKeyboardButton
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    builder = InlineKeyboardBuilder()
    for user in users:
        user_text = f"{user.first_name}"
        if user.username:
            user_text += f" @{user.username}"
        user_text += f" (ID: {user.telegram_id})"
        builder.row(InlineKeyboardButton(
            text=user_text,
            callback_data=f"admin:ban:select:{user.id}"
        ))
    builder.row(InlineKeyboardButton(
        text="🔍 Поиск по ID/Username",
        callback_data="admin:bans:search:manual"
    ))
    builder.row(InlineKeyboardButton(
        text="« Назад",
        callback_data="admin:bans"
    ))
    await safe_edit_text(
        callback,
        "👥 <b>Выберите пользователя для блокировки</b>\n\n"
        "<i>Показаны последние 20 активных пользователей</i>",
        reply_markup=builder.as_markup()
    )
@router.callback_query(F.data == "admin:bans:search:manual")
async def search_user_to_ban_manual(callback: CallbackQuery, state: FSMContext):
    await callback.message.answer(
        "🔍 <b>Поиск пользователя</b>\n\n"
        "Отправьте Telegram ID или username пользователя для блокировки:\n\n"
        "Примеры:\n"
        "• <code>123456789</code> (Telegram ID)\n"
        "• <code>@username</code> (Username)",
        parse_mode="HTML"
    )
    await state.set_state(BanStates.waiting_for_user_id)
@router.callback_query(F.data.startswith("admin:ban:select:"))
async def select_user_to_ban(callback: CallbackQuery, session: AsyncSession):
    user_id = int(callback.data.split(":")[3])
    result = await session.execute(
        select(User).where(User.id == user_id)
    )
    user = result.scalars().first()
    if not user:
        await callback.answer("Пользователь не найден", show_alert=True)
        return
    ban_status = ""
    if user.is_banned:
        ban_status = "\n🚫 <b>Уже заблокирован навсегда</b>"
    elif user.ban_until and user.ban_until > datetime.now():
        ban_status = f"\n⏱ <b>Временно заблокирован до:</b> {user.ban_until.strftime('%d.%m.%Y %H:%M')}"
    msg_text = (
        f"👤 <b>Выбран пользователь:</b>\n\n"
        f"ID: <code>{user.telegram_id}</code>\n"
        f"Имя: {user.first_name}\n"
        f"Username: @{user.username if user.username else 'нет'}\n"
        f"{ban_status}\n\n"
        f"Выберите срок блокировки:"
    )
    await safe_edit_text(
        callback,
        msg_text,
        reply_markup=ban_duration_menu(user.id)
    )
@router.message(BanStates.waiting_for_user_id)
async def process_user_search(message, session: AsyncSession, state: FSMContext):
    search_query = message.text.strip()
    if search_query.startswith('@'):
        username = search_query[1:]
        result = await session.execute(
            select(User).where(User.username == username)
        )
    else:
        try:
            user_id = int(search_query)
            result = await session.execute(
                select(User).where(User.telegram_id == user_id)
            )
        except ValueError:
            await message.answer("❌ Неверный формат. Введите Telegram ID (число) или @username")
            return
    user = result.scalars().first()
    if not user:
        await message.answer("❌ Пользователь не найден")
        return
    if user.role == "ADMIN":
        await message.answer("❌ Невозможно заблокировать администратора")
        await state.clear()
        return
    await state.update_data(target_user_id=user.id)
    ban_status = ""
    if user.is_banned:
        ban_status = "\n🚫 <b>Уже заблокирован навсегда</b>"
    elif user.ban_until and user.ban_until > datetime.now():
        ban_status = f"\n⏱ <b>Временно заблокирован до:</b> {user.ban_until.strftime('%d.%m.%Y %H:%M')}"
    msg_text = (
        f"👤 <b>Найден пользователь:</b>\n\n"
        f"ID: <code>{user.telegram_id}</code>\n"
        f"Имя: {user.first_name}\n"
        f"Username: @{user.username if user.username else 'нет'}\n"
        f"{ban_status}\n\n"
        f"Выберите срок блокировки:"
    )
    await message.answer(
        msg_text,
        reply_markup=ban_duration_menu(user.id)
    )
    await state.clear()
@router.callback_query(F.data.startswith("admin:ban:duration:"))
async def select_ban_duration(callback: CallbackQuery, session: AsyncSession, state: FSMContext):
    parts = callback.data.split(":")
    user_id = int(parts[3])
    duration = parts[4]
    result = await session.execute(
        select(User).where(User.id == user_id)
    )
    target_user = result.scalars().first()
    if not target_user:
        await callback.answer("Пользователь не найден", show_alert=True)
        return
    await state.update_data(
        target_user_id=user_id,
        ban_duration=duration
    )
    duration_text = {
        '1h': '1 час',
        '1d': '1 день',
        '1m': '1 месяц',
        '1y': '1 год',
        'forever': 'навсегда'
    }.get(duration, duration)
    await callback.message.answer(
        f"📝 <b>Блокировка на {duration_text}</b>\n\n"
        f"Пользователь: {target_user.first_name}\n"
        f"ID: <code>{target_user.telegram_id}</code>\n\n"
        f"Укажите причину блокировки:",
        parse_mode="HTML"
    )
    await state.set_state(BanStates.waiting_for_ban_reason)
@router.message(BanStates.waiting_for_ban_reason)
async def process_ban_with_reason(message, session: AsyncSession, state: FSMContext, user: User):
    data = await state.get_data()
    target_user_id = data.get('target_user_id')
    duration = data.get('ban_duration')
    reason = message.text.strip()
    result = await session.execute(
        select(User).where(User.id == target_user_id)
    )
    target_user = result.scalars().first()
    if not target_user:
        await message.answer("❌ Пользователь не найден")
        await state.clear()
        return
    ban_until = None
    if duration == '1h':
        ban_until = datetime.now() + timedelta(hours=1)
    elif duration == '1d':
        ban_until = datetime.now() + timedelta(days=1)
    elif duration == '1m':
        ban_until = datetime.now() + timedelta(days=30)
    elif duration == '1y':
        ban_until = datetime.now() + timedelta(days=365)
    elif duration == 'forever':
        target_user.is_banned = True
    target_user.ban_until = ban_until
    target_user.ban_reason = reason
    target_user.banned_by = user.id
    target_user.banned_at = datetime.now()
    await session.commit()
    duration_text = {
        '1h': 'на 1 час',
        '1d': 'на 1 день',
        '1m': 'на 1 месяц',
        '1y': 'на 1 год',
        'forever': 'навсегда'
    }.get(duration, duration)
    ban_until_text = f" до {ban_until.strftime('%d.%m.%Y %H:%M')}" if ban_until else ""
    success_msg = (
        f"✅ <b>Пользователь заблокирован</b>\n\n"
        f"👤 {target_user.first_name}\n"
        f"ID: <code>{target_user.telegram_id}</code>\n\n"
        f"⏱ Срок: {duration_text}{ban_until_text}\n"
        f"📝 Причина: {reason}\n"
        f"👮 Заблокировал: {user.first_name}"
    )
    await message.answer(success_msg, parse_mode="HTML")
    from bot.loader import bot
    from bot.config import load_config
    config = load_config()
    admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
    await admin_notifier.notify_user_banned(
        target_user.telegram_id,
        target_user.first_name,
        duration_text,
        reason,
        user.first_name
    )
    await state.clear()
@router.callback_query(F.data == "admin:bans:list")
async def show_banned_users(callback: CallbackQuery, session: AsyncSession):
    result = await session.execute(
        select(User).where(
            (User.is_banned == True) |
            ((User.ban_until.isnot(None)) & (User.ban_until > datetime.now()))
        ).order_by(User.banned_at.desc()).limit(20)
    )
    banned_users = result.scalars().all()
    if not banned_users:
        await safe_edit_text(
            callback,
            "📋 <b>Список заблокированных</b>\n\n"
            "Нет заблокированных пользователей",
            reply_markup=back_to_bans_menu()
        )
        return
    msg_lines = ["📋 <b>Заблокированные пользователи</b>\n"]
    for user in banned_users:
        ban_info = ""
        if user.is_banned:
            ban_info = "🚫 Навсегда"
        elif user.ban_until:
            ban_info = f"⏱ До {user.ban_until.strftime('%d.%m.%Y %H:%M')}"
        msg_lines.append(
            f"\n👤 {user.first_name}\n"
            f"ID: <code>{user.telegram_id}</code>\n"
            f"{ban_info}\n"
            f"Причина: {user.ban_reason or 'не указана'}"
        )
    msg_lines.append("\n\n<i>Показаны последние 20 заблокированных</i>")
    await safe_edit_text(
        callback,
        "\n".join(msg_lines),
        reply_markup=back_to_bans_menu()
    )
@router.callback_query(F.data == "admin:bans:unban")
async def show_users_to_unban(callback: CallbackQuery, session: AsyncSession):
    result = await session.execute(
        select(User).where(
            (User.is_banned == True) |
            ((User.ban_until.isnot(None)) & (User.ban_until > datetime.now()))
        ).order_by(User.banned_at.desc()).limit(20)
    )
    banned_users = result.scalars().all()
    if not banned_users:
        await callback.answer("Нет заблокированных пользователей", show_alert=True)
        return
    from aiogram.types import InlineKeyboardButton
    from aiogram.utils.keyboard import InlineKeyboardBuilder
    builder = InlineKeyboardBuilder()
    for user in banned_users:
        ban_info = "🚫 Навсегда" if user.is_banned else f"⏱ До {user.ban_until.strftime('%d.%m')}"
        user_text = f"{user.first_name}"
        if user.username:
            user_text += f" @{user.username}"
        user_text += f" - {ban_info}"
        builder.row(InlineKeyboardButton(
            text=user_text,
            callback_data=f"admin:unban:confirm:{user.id}"
        ))
    builder.row(InlineKeyboardButton(
        text="🔍 Поиск по ID",
        callback_data="admin:bans:unban:manual"
    ))
    builder.row(InlineKeyboardButton(
        text="« Назад",
        callback_data="admin:bans"
    ))
    await safe_edit_text(
        callback,
        "🔓 <b>Выберите пользователя для разблокировки</b>\n\n"
        "<i>Показаны последние 20 заблокированных</i>",
        reply_markup=builder.as_markup()
    )
@router.callback_query(F.data == "admin:bans:unban:manual")
async def unban_user_search_manual(callback: CallbackQuery, state: FSMContext):
    await callback.message.answer(
        "🔓 <b>Разблокировка пользователя</b>\n\n"
        "Отправьте Telegram ID пользователя для разблокировки:",
        parse_mode="HTML"
    )
    await state.set_state(BanStates.waiting_for_unban_user_id)
@router.callback_query(F.data.startswith("admin:unban:confirm:"))
async def confirm_unban_user(callback: CallbackQuery, session: AsyncSession, user: User):
    target_user_id = int(callback.data.split(":")[3])
    result = await session.execute(
        select(User).where(User.id == target_user_id)
    )
    target_user = result.scalars().first()
    if not target_user:
        await callback.answer("Пользователь не найден", show_alert=True)
        return
    target_user.is_banned = False
    target_user.ban_until = None
    target_user.ban_reason = None
    await session.commit()
    await callback.message.answer(
        f"✅ <b>Пользователь разблокирован</b>\n\n"
        f"👤 {target_user.first_name}\n"
        f"ID: <code>{target_user.telegram_id}</code>\n\n"
        f"👮 Разблокировал: {user.first_name}",
        parse_mode="HTML"
    )
    await show_bans_menu(callback, session)
@router.message(BanStates.waiting_for_unban_user_id)
async def process_unban(message, session: AsyncSession, state: FSMContext, user: User):
    try:
        target_user_id = int(message.text.strip())
    except ValueError:
        await message.answer("❌ Неверный формат. Введите Telegram ID (число)")
        return
    result = await session.execute(
        select(User).where(User.telegram_id == target_user_id)
    )
    target_user = result.scalars().first()
    if not target_user:
        await message.answer("❌ Пользователь не найден")
        return
    target_user.is_banned = False
    target_user.ban_until = None
    target_user.ban_reason = None
    await session.commit()
    await message.answer(
        f"✅ <b>Пользователь разблокирован</b>\n\n"
        f"👤 {target_user.first_name}\n"
        f"ID: <code>{target_user.telegram_id}</code>\n\n"
        f"👮 Разблокировал: {user.first_name}",
        parse_mode="HTML"
    )
    await state.clear()