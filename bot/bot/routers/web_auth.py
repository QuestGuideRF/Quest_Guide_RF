from datetime import datetime, timedelta
from aiogram import Router, F
from aiogram.filters import Command
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton, WebAppInfo
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user_session import UserSession
from bot.loader import config
from bot.utils.i18n import i18n
router = Router()
async def _send_web_access(target, session: AsyncSession, user, from_user_id: int):
    from bot.repositories.user import UserRepository
    user_repo = UserRepository(session)
    full_user = await user_repo.get_by_telegram_id(from_user_id)
    telegram_id = from_user_id
    token = UserSession.generate_token()
    user_session = UserSession(
        telegram_id=telegram_id,
        token=token,
        is_used=False,
        created_at=datetime.utcnow(),
        expires_at=datetime.utcnow() + timedelta(minutes=5),
    )
    session.add(user_session)
    await session.commit()
    webapp_url = f"{config.web.site_url}/webapp/index.php"
    user_language = user.language if user.language else "ru"
    if full_user and (full_user.role == "ADMIN" or full_user.role.upper() == "ADMIN"):
        auth_url = f"{config.web.site_url}/admin/login.php?token={token}"
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(
                text=i18n.get("web_login_button", user_language),
                web_app=WebAppInfo(url=webapp_url)
            )],
            [InlineKeyboardButton(
                text=i18n.get("web_admin_panel_button", user_language),
                url=auth_url
            )],
            [InlineKeyboardButton(
                text=i18n.get("web_open_browser_button", user_language),
                url=f"{config.web.site_url}/auth/telegram.php?token={token}"
            )]
        ])
        await target.answer(
            f"{i18n.get('web_access_title', user_language)}\n\n"
            f"{i18n.get('web_access_message_admin', user_language)}",
            reply_markup=keyboard
        )
    elif full_user and (full_user.role == "MODERATOR" or full_user.role.upper() == "MODERATOR"):
        auth_url = f"{config.web.site_url}/admin/login.php?token={token}"
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(
                text=i18n.get("web_login_button", user_language),
                web_app=WebAppInfo(url=webapp_url)
            )],
            [InlineKeyboardButton(
                text=i18n.get("web_moderator_panel_button", user_language),
                url=auth_url
            )],
            [InlineKeyboardButton(
                text=i18n.get("web_open_browser_button", user_language),
                url=f"{config.web.site_url}/auth/telegram.php?token={token}"
            )]
        ])
        await target.answer(
            f"{i18n.get('web_access_title', user_language)}\n\n"
            f"{i18n.get('web_access_message_moderator', user_language)}",
            reply_markup=keyboard
        )
    else:
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(
                text=i18n.get("web_login_button", user_language),
                web_app=WebAppInfo(url=webapp_url)
            )],
            [InlineKeyboardButton(
                text=i18n.get("web_open_browser_button", user_language),
                url=f"{config.web.site_url}/auth/telegram.php?token={token}"
            )]
        ])
        await target.answer(
            f"{i18n.get('web_access_title', user_language)}\n\n"
            f"{i18n.get('web_access_message', user_language)}",
            reply_markup=keyboard
        )
@router.message(Command("web"))
async def cmd_web(message: Message, session: AsyncSession, user):
    await _send_web_access(message, session, user, message.from_user.id)
@router.callback_query(F.data == "open_web")
async def cb_open_web(callback: CallbackQuery, session: AsyncSession, user):
    await _send_web_access(callback.message, session, user, callback.from_user.id)
    await callback.answer()
@router.message(Command("admin"))
async def cmd_admin(message: Message, session: AsyncSession, user):
    if user.role != "ADMIN":
        await message.answer(
            "⛔️ Доступ запрещен\n\n"
            "Эта команда доступна только администраторам."
        )
        return
    telegram_id = message.from_user.id
    token = UserSession.generate_token()
    user_session = UserSession(
        telegram_id=telegram_id,
        token=token,
        is_used=False,
        created_at=datetime.utcnow(),
        expires_at=datetime.utcnow() + timedelta(minutes=10),
    )
    session.add(user_session)
    await session.commit()
    admin_url = f"{config.web.site_url}/admin/login.php?token={token}"
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="🔐 Войти в админ-панель", url=admin_url)]
    ])
    await message.answer(
        "🔐 Вход в админ-панель\n\n"
        "Нажмите кнопку ниже, чтобы войти в панель администратора.\n\n"
        "⚠️ Ссылка действительна 10 минут.\n"
        "🔒 Не передавайте эту ссылку другим людям!",
        reply_markup=keyboard
    )