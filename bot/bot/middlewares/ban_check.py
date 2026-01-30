import logging
from typing import Callable, Dict, Any, Awaitable, Union
from datetime import datetime
from aiogram import BaseMiddleware
from aiogram.types import Message, CallbackQuery
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
logger = logging.getLogger(__name__)
class BanCheckMiddleware(BaseMiddleware):
    async def __call__(
        self,
        handler: Callable[[Union[Message, CallbackQuery], Dict[str, Any]], Awaitable[Any]],
        event: Union[Message, CallbackQuery],
        data: Dict[str, Any]
    ) -> Any:
        session: AsyncSession = data.get("session")
        user_id = event.from_user.id
        if session:
            try:
                result = await session.execute(
                    text("SELECT is_banned, ban_until, ban_reason, banned_at FROM users WHERE telegram_id = :telegram_id"),
                    {"telegram_id": user_id}
                )
                row = result.fetchone()
                if row:
                    is_banned, ban_until, ban_reason, banned_at = row
                    if is_banned == 1:
                        ban_message = (
                            "🚫 <b>Вы заблокированы навсегда</b>\n\n"
                            f"📝 Причина: {ban_reason or 'не указана'}\n"
                        )
                        if banned_at:
                            ban_message += f"📅 Дата блокировки: {banned_at.strftime('%d.%m.%Y %H:%M')}\n"
                        ban_message += "\n💬 Для разблокировки обратитесь в поддержку."
                        if isinstance(event, Message):
                            await event.answer(ban_message, parse_mode="HTML")
                        elif isinstance(event, CallbackQuery):
                            await event.answer("🚫 Вы заблокированы навсегда", show_alert=True)
                        return
                    if ban_until and ban_until > datetime.now():
                        time_left = ban_until - datetime.now()
                        days = time_left.days
                        hours = time_left.seconds // 3600
                        minutes = (time_left.seconds % 3600) // 60
                        time_str = []
                        if days > 0:
                            time_str.append(f"{days} дн.")
                        if hours > 0:
                            time_str.append(f"{hours} ч.")
                        if minutes > 0 and days == 0:
                            time_str.append(f"{minutes} мин.")
                        time_left_text = " ".join(time_str) if time_str else "менее минуты"
                        ban_message = (
                            f"🚫 <b>Вы временно заблокированы</b>\n\n"
                            f"⏱ До окончания: {time_left_text}\n"
                            f"📅 До: {ban_until.strftime('%d.%m.%Y %H:%M')}\n"
                            f"📝 Причина: {ban_reason or 'не указана'}\n"
                        )
                        if banned_at:
                            ban_message += f"📅 Заблокирован: {banned_at.strftime('%d.%m.%Y %H:%M')}\n"
                        if isinstance(event, Message):
                            await event.answer(ban_message, parse_mode="HTML")
                        elif isinstance(event, CallbackQuery):
                            await event.answer(
                                f"🚫 Заблокирован до {ban_until.strftime('%d.%m.%Y %H:%M')}",
                                show_alert=True
                            )
                        return
            except Exception as e:
                logger.error(f"Ошибка проверки блокировки: {e}")
        return await handler(event, data)