import logging
from typing import Callable, Dict, Any, Awaitable
from aiogram import BaseMiddleware
from aiogram.types import TelegramObject, Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton
from bot.loader import config
logger = logging.getLogger(__name__)
class ChannelCheckMiddleware(BaseMiddleware):
    def __init__(self):
        self.config = config
        super().__init__()
    async def __call__(
        self,
        handler: Callable[[TelegramObject, Dict[str, Any]], Awaitable[Any]],
        event: TelegramObject,
        data: Dict[str, Any],
    ) -> Any:
        session = data.get("session")
        subscription_check_enabled = None
        if session:
            try:
                from bot.utils.settings import is_subscription_check_enabled
                subscription_check_enabled = await is_subscription_check_enabled(session)
                if not subscription_check_enabled:
                    return await handler(event, data)
            except Exception as e:
                logger.warning(f"Ошибка проверки настройки подписки из БД: {e}, используем конфиг")
        if subscription_check_enabled is None:
            if not self.config.channel.require_subscription:
                return await handler(event, data)
        if (not self.config.channel.channel_id or self.config.channel.channel_id == 0) and not self.config.channel.channel_username:
            logger.warning("Проверка подписки включена, но канал не настроен")
            return await handler(event, data)
        user = data.get("user")
        if not user:
            return await handler(event, data)
        if hasattr(user, 'role') and user.role in ['ADMIN', 'MODERATOR']:
            return await handler(event, data)
        message = None
        if isinstance(event, Message):
            message = event
        elif isinstance(event, CallbackQuery):
            message = event.message
        if isinstance(event, Message):
            if event.text and event.text.startswith("/start"):
                return await handler(event, data)
            if event.text and event.text.startswith("/top"):
                return await handler(event, data)
        elif isinstance(event, CallbackQuery):
            allowed_callbacks = [
                "check_subscription",
                "lang:",
                "my_stats",
                "settings",
                "settings:",
                "about",
                "select_city",
                "city:",
                "route:",
                "back_to_main",
                "back_to_routes",
                "top",
            ]
            if event.data:
                for allowed in allowed_callbacks:
                    if event.data.startswith(allowed):
                        return await handler(event, data)
        try:
            from bot.loader import bot
            from bot.utils.i18n import i18n
            from aiogram.exceptions import TelegramBadRequest
            user_id = user.telegram_id if hasattr(user, 'telegram_id') else user.id
            member = None
            chat_id = None
            if self.config.channel.channel_id and self.config.channel.channel_id != 0:
                try:
                    channel_id = self.config.channel.channel_id
                    if channel_id > 0:
                        channel_id = -1000000000000 - channel_id
                    member = await bot.get_chat_member(
                        chat_id=channel_id,
                        user_id=user_id
                    )
                    chat_id = channel_id
                except TelegramBadRequest as e:
                    error_msg = str(e).lower()
                    if "member list is inaccessible" in error_msg:
                        logger.error(f"⚠️ Бот не может проверить подписку на канал (ID: {self.config.channel.channel_id}): бот должен быть администратором канала с правами просмотра участников. Пробуем username...")
                        if self.config.channel.channel_username:
                            try:
                                member = await bot.get_chat_member(
                                    chat_id=f"@{self.config.channel.channel_username}",
                                    user_id=user_id
                                )
                                chat_id = f"@{self.config.channel.channel_username}"
                            except TelegramBadRequest as e2:
                                error_msg2 = str(e2).lower()
                                if "member list is inaccessible" in error_msg2 or "chat not found" in error_msg2:
                                    logger.error(f"⚠️ Бот не может проверить подписку на канал @{self.config.channel.channel_username}: бот должен быть администратором канала с правами просмотра участников. Проверка подписки отключена.")
                                    return await handler(event, data)
                                else:
                                    raise
                        else:
                            logger.error(f"⚠️ Бот не может проверить подписку: бот должен быть администратором канала с правами просмотра участников. Проверка подписки отключена.")
                            return await handler(event, data)
                    elif "chat not found" in error_msg:
                        if self.config.channel.channel_username:
                            try:
                                member = await bot.get_chat_member(
                                    chat_id=f"@{self.config.channel.channel_username}",
                                    user_id=user_id
                                )
                                chat_id = f"@{self.config.channel.channel_username}"
                            except TelegramBadRequest as e2:
                                error_msg2 = str(e2).lower()
                                if "member list is inaccessible" in error_msg2:
                                    logger.error(f"⚠️ Бот не может проверить подписку на канал @{self.config.channel.channel_username}: бот должен быть администратором канала с правами просмотра участников. Проверка подписки отключена.")
                                    return await handler(event, data)
                                elif "chat not found" in error_msg2:
                                    logger.warning(f"Канал не найден. Пропускаем проверку подписки.")
                                    return await handler(event, data)
                                else:
                                    raise
                        else:
                            logger.warning(f"Канал не найден. Пропускаем проверку подписки.")
                            return await handler(event, data)
                    else:
                        raise
            elif self.config.channel.channel_username:
                try:
                    member = await bot.get_chat_member(
                        chat_id=f"@{self.config.channel.channel_username}",
                        user_id=user_id
                    )
                    chat_id = f"@{self.config.channel.channel_username}"
                except TelegramBadRequest as e:
                    error_msg = str(e).lower()
                    if "member list is inaccessible" in error_msg:
                        logger.error(f"⚠️ Бот не может проверить подписку на канал @{self.config.channel.channel_username}: бот должен быть администратором канала с правами просмотра участников. Проверка подписки отключена.")
                        return await handler(event, data)
                    elif "chat not found" in error_msg:
                        logger.warning(f"Канал @{self.config.channel.channel_username} не найден. Пропускаем проверку подписки.")
                        return await handler(event, data)
                    else:
                        raise
            else:
                logger.warning("Канал не настроен: нет ни ID, ни username")
                return await handler(event, data)
            status_val = getattr(member.status, 'value', str(member.status)).lower() if member.status else ''
            is_subscribed = status_val in ('member', 'administrator', 'creator')
            if not is_subscribed:
                channel_username = self.config.channel.channel_username or "questguiderf"
                channel_link = f"https://t.me/{channel_username}"
                user_lang = getattr(user, 'language', 'ru') if hasattr(user, 'language') else 'ru'
                keyboard = InlineKeyboardMarkup(inline_keyboard=[
                    [InlineKeyboardButton(
                        text=i18n.get("channel_button", user_lang, default="📢 Перейти в канал"),
                        url=channel_link
                    )],
                    [InlineKeyboardButton(
                        text=i18n.get("subscribe_button", user_lang, default="✅ Я подписался"),
                        callback_data="check_subscription"
                    )]
                ])
                subscribe_text = i18n.get("subscribe_required", user_lang)
                if not subscribe_text or subscribe_text == "subscribe_required":
                    subscribe_text = f"📢 Для использования бота необходимо подписаться на канал.\n\nПосле подписки нажмите кнопку ниже."
                if isinstance(event, Message):
                    await message.answer(subscribe_text, reply_markup=keyboard, parse_mode="HTML")
                elif isinstance(event, CallbackQuery):
                    if message:
                        await message.edit_text(subscribe_text, reply_markup=keyboard, parse_mode="HTML")
                    fail_text = i18n.get("subscribe_fail", user_lang)
                    if not fail_text or fail_text == "subscribe_fail":
                        fail_text = f"❌ Вы не подписаны на канал. Пожалуйста, подпишитесь: {channel_link}"
                    await event.answer(fail_text, show_alert=True)
                return
        except Exception as e:
            logger.error(f"Ошибка проверки подписки для пользователя {user.telegram_id if hasattr(user, 'telegram_id') else 'unknown'}: {e}")
            return await handler(event, data)
        return await handler(event, data)