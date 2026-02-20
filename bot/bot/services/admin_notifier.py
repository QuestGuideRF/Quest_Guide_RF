<<<<<<< HEAD
import asyncio
import html
import logging
from decimal import Decimal
=======
import html
import logging
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from typing import List, Optional
from aiogram import Bot
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton, FSInputFile
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
logger = logging.getLogger(__name__)
<<<<<<< HEAD
_review_buffer: List[dict] = []
_review_flush_task: Optional[asyncio.Task] = None
_review_bot: Optional[Bot] = None
_review_admin_ids: List[int] = []
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
class AdminNotifier:
    def __init__(self, bot: Bot, admin_ids: List[int]):
        self.bot = bot
        self.admin_ids = admin_ids
    async def is_restart_notifications_enabled(self, session: AsyncSession) -> bool:
        try:
            result = await session.execute(
                text("SELECT value FROM system_settings WHERE `key` = 'restart_notifications_enabled'")
            )
            row = result.fetchone()
            if row:
                return row[0] == '1' or row[0].lower() == 'true'
            return True
        except Exception as e:
            logger.error(f"Ошибка проверки настройки уведомлений: {e}")
            return True
<<<<<<< HEAD
    async def is_payment_notifications_enabled(self, session: AsyncSession) -> bool:
        try:
            result = await session.execute(
                text("SELECT value FROM system_settings WHERE `key` = 'payment_notifications_enabled'")
            )
            row = result.fetchone()
            if row:
                return row[0] == '1' or row[0].lower() == 'true'
            return True
        except Exception as e:
            logger.error(f"Ошибка проверки настройки уведомлений о платежах: {e}")
            return True
    async def notify_balance_deposit(
        self,
        session: AsyncSession,
        user_id: int,
        username: Optional[str],
        first_name: Optional[str],
        amount: Decimal,
        payment_method: str = "payment",
    ):
        if not await self.is_payment_notifications_enabled(session):
            return
        user_link = f"@{username}" if username else f"ID {user_id}"
        if first_name:
            user_link = f"{html.escape(first_name)} ({user_link})"
        else:
            user_link = html.escape(user_link)
        amount_str = f"{amount:.0f}" if amount == int(amount) else f"{amount:.2f}"
        method_label = "ЮKassa" if payment_method and "yookassa" in str(payment_method).lower() else ("Stars" if "star" in str(payment_method).lower() else "Оплата")
        message = (
            f"💰 <b>Пополнение баланса</b>\n\n"
            f"👤 Пользователь: {user_link}\n"
            f"💵 Сумма: {amount_str} грошей\n"
            f"📱 Способ: {method_label}"
        )
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    chat_id=admin_id,
                    text=message,
                    parse_mode="HTML",
                )
                logger.info(f"Отправлено уведомление о пополнении админу {admin_id}, пользователь {user_id}, сумма {amount}")
            except Exception as e:
                logger.error(f"Ошибка отправки уведомления о пополнении админу {admin_id}: {e}")
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    async def notify_photo_verification_needed(
        self,
        photo_path: str,
        user_id: int,
        username: Optional[str],
        point_name: str,
        point_id: int,
        progress_id: int,
        photo_file_id: str,
        route_name: str,
        error_reason: str,
        people_count: Optional[int] = None,
<<<<<<< HEAD
=======
        pose_required: Optional[str] = None,
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        location_match: Optional[float] = None,
        is_manual_moderation: bool = False,
    ):
        user_link = f"@{username}" if username else f"ID: {user_id}"
        user_link = html.escape(user_link)
        route_name = html.escape(route_name)
        point_name = html.escape(point_name)
        error_reason = html.escape(error_reason)
        if is_manual_moderation:
            message = (
                f"🔍 <b>Требуется проверка фото</b>\n\n"
                f"👤 Пользователь: {user_link}\n"
                f"🗺️ Маршрут: {route_name}\n"
                f"📍 Точка: {point_name}\n\n"
                f"👮 <b>Режим ручной модерации</b>\n\n"
            )
        else:
            message = (
                f"🔍 <b>Требуется проверка фото</b>\n\n"
                f"👤 Пользователь: {user_link}\n"
                f"🗺️ Маршрут: {route_name}\n"
                f"📍 Точка: {point_name}\n\n"
                f"❌ <b>Причина отклонения:</b>\n{error_reason}\n\n"
            )
        if people_count is not None:
            message += f"👥 Людей на фото: {people_count}\n"
<<<<<<< HEAD
=======
        if pose_required:
            pose_names = {
                'hands_up': 'Руки вверх',
                'heart': 'Сердечко руками',
                'point': 'Указать на объект'
            }
            message += f"🤸 Требуемая поза: {pose_names.get(pose_required, pose_required)}\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        if location_match is not None:
            message += f"📸 Совпадение локации: {location_match:.1f}%\n"
        message += "\n<b>Принять фото?</b>"
        callback_approve = f"appr:{user_id}:{point_id}:{progress_id}"
        callback_reject = f"rej:{user_id}"
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [
                InlineKeyboardButton(
                    text="✅ Принять",
                    callback_data=callback_approve[:64]
                ),
                InlineKeyboardButton(
                    text="❌ Отклонить",
                    callback_data=callback_reject[:64]
                )
            ]
        ])
        for admin_id in self.admin_ids:
            try:
                photo = FSInputFile(photo_path)
                await self.bot.send_photo(
                    chat_id=admin_id,
                    photo=photo,
                    caption=message,
                    reply_markup=keyboard,
                    parse_mode="HTML"
                )
                logger.info(f"Отправлено уведомление админу {admin_id} о фото от пользователя {user_id}")
            except Exception as e:
                logger.error(f"Ошибка отправки уведомления админу {admin_id}: {e}", exc_info=True)
    async def notify_critical_error(self, error_message: str, error_details: Optional[str] = None):
        message = f"🚨 <b>КРИТИЧЕСКАЯ ОШИБКА</b>\n\n{error_message}"
        if error_details:
            if len(error_details) > 3000:
                error_details = error_details[:3000] + "...\n\n(обрезано)"
            message += f"\n\n<pre>{error_details}</pre>"
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    chat_id=admin_id,
                    text=message,
                    parse_mode="HTML"
                )
                logger.info(f"Отправлено уведомление о критической ошибке админу {admin_id}")
            except Exception as e:
                logger.error(f"Ошибка отправки критической ошибки админу {admin_id}: {e}")
    async def notify_bot_restart(self, error_log: Optional[str] = None, session: Optional[AsyncSession] = None):
        if session:
            if not await self.is_restart_notifications_enabled(session):
                logger.info("Уведомления о перезапуске отключены в настройках")
                return
        message = "🔄 <b>Бот перезапущен</b>\n\n"
        if error_log:
            message += "📋 <b>Последние ошибки:</b>\n"
            if len(error_log) > 3000:
                error_log = error_log[-3000:]
            message += f"<pre>{error_log}</pre>"
        else:
            message += "✅ Перезапуск прошёл успешно, ошибок не обнаружено."
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    chat_id=admin_id,
                    text=message,
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Ошибка отправки уведомления о перезапуске админу {admin_id}: {e}")
    async def notify_bot_stopped(self, stopped_by_user_id: int, stopped_by_username: Optional[str] = None):
        username_text = f" (@{stopped_by_username})" if stopped_by_username else ""
        message = (
            f"🛑 <b>Бот остановлен</b>\n\n"
            f"Остановлен администратором: {stopped_by_user_id}{username_text}\n\n"
            f"Для повторного запуска используйте скрипт start.sh или CRON."
        )
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    chat_id=admin_id,
                    text=message,
                    parse_mode="HTML"
                )
                logger.info(f"Отправлено уведомление об остановке админу {admin_id}")
            except Exception as e:
                logger.error(f"Ошибка отправки уведомления об остановке админу {admin_id}: {e}")
    async def notify_user_banned(
        self,
        banned_user_id: int,
        banned_username: str,
        duration: str,
        reason: str,
        admin_name: str
    ):
        from datetime import datetime
        message = (
            f"🚫 <b>Пользователь заблокирован</b>\n\n"
            f"👤 Пользователь: {banned_username} (ID: {banned_user_id})\n"
            f"⏱ Срок: {duration}\n"
            f"📝 Причина: {reason}\n"
            f"👮 Заблокировал: {admin_name}\n"
            f"⏰ Время: {datetime.now().strftime('%d.%m.%Y %H:%M:%S')}"
        )
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    admin_id,
                    message,
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Не удалось отправить уведомление админу {admin_id}: {e}")
<<<<<<< HEAD
    async def notify_moderator_request(
        self,
        user_first_name: Optional[str],
        user_username: Optional[str],
        user_telegram_id: int,
        request_text: str,
        user_id: int = None,
        request_id: int = None,
    ):
        from bot.keyboards.admin import moderator_request_actions
        user_link = f"@{user_username}" if user_username else f"ID: {user_telegram_id}"
        user_link = html.escape(user_link)
        request_text_esc = html.escape(request_text)
        message = (
            f"📩 <b>Новая заявка на модератора</b>\n\n"
            f"👤 От: {html.escape(user_first_name or '')} {user_link}\n"
            f"🆔 ID: {user_telegram_id}\n\n"
            f"📝 Сообщение:\n{request_text_esc}"
        )
        keyboard = None
        if user_id and request_id:
            keyboard = moderator_request_actions(user_id, request_id)
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(
                    chat_id=admin_id,
                    text=message,
                    parse_mode="HTML",
                    reply_markup=keyboard,
                )
                logger.info(f"Отправлено уведомление о заявке на модератора админу {admin_id}")
            except Exception as e:
                logger.error(f"Ошибка отправки уведомления о заявке админу {admin_id}: {e}")
    async def notify_new_review(
        self,
        user_id: int,
        username: Optional[str],
        route_name: str,
        rating: int,
        text: Optional[str] = None,
        first_name: Optional[str] = None,
    ):
        global _review_buffer, _review_flush_task, _review_bot, _review_admin_ids
        _review_buffer.append({
            "user_id": user_id,
            "username": username,
            "first_name": first_name,
            "route_name": route_name,
            "rating": rating,
            "text": text,
        })
        _review_bot = self.bot
        _review_admin_ids = list(self.admin_ids)
        if _review_flush_task is None or _review_flush_task.done():
            _review_flush_task = asyncio.create_task(_flush_reviews_after_delay())
    @staticmethod
    async def flush_reviews_now():
        await _do_flush_reviews()
    async def notify_survey_results(
        self,
        user_id: int,
        username: Optional[str],
        route_name: str,
        answers: dict,
    ):
        if not self.admin_ids:
            logger.debug("Нет admin_ids, уведомление о опросе не отправляется")
            return
        user_link = f"@{username}" if username else f"ID: {user_id}"
        user_link = html.escape(user_link)
        route_name = html.escape(route_name)
        difficulty = answers.get("difficulty", "—")
        navigation = answers.get("navigation", "—")
        liked = (answers.get("liked") or "").strip() or "—"
        liked = html.escape(liked[:500])
        had_problems = answers.get("had_problems", False)
        problems_text = (answers.get("problems_text") or "").strip() or "—"
        problems_text = html.escape(problems_text[:300])
        improve = (answers.get("improve") or "").strip() or "—"
        improve = html.escape(improve[:500])
        msg = (
            f"📋 <b>Результаты опроса</b>\n\n"
            f"👤 Пользователь: {user_link}\n"
            f"🗺 Маршрут: {route_name}\n\n"
            f"📊 Сложность (1–5): {difficulty}\n"
            f"🧭 Навигация (1–5): {navigation}\n"
            f"😊 Что понравилось: {liked}\n"
            f"🔧 Были проблемы: {'Да' if had_problems else 'Нет'}\n"
        )
        if had_problems:
            msg += f"📝 Описание проблемы: {problems_text}\n"
        msg += f"💡 Что улучшить: {improve}"
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(admin_id, msg, parse_mode="HTML")
                logger.info("Отправлены результаты опроса админу %s", admin_id)
            except Exception as e:
                logger.error("Не удалось отправить результаты опроса админу %s: %s", admin_id, e)
async def _flush_reviews_after_delay() -> None:
    await asyncio.sleep(15)
    await _do_flush_reviews()
async def _do_flush_reviews() -> None:
    global _review_buffer, _review_flush_task
    if not _review_buffer:
        return
    to_send = _review_buffer[:]
    _review_buffer = []
    _review_flush_task = None
    bot = _review_bot
    admin_ids = _review_admin_ids
    if not bot or not admin_ids:
        return
    parts = [f"⭐ <b>Новые отзывы</b> ({len(to_send)}):\n"]
    for i, r in enumerate(to_send, 1):
        stars = "⭐" * r["rating"]
        route_safe = html.escape(str(r["route_name"]))
        user_parts = []
        if r.get("first_name"):
            user_parts.append(html.escape(r["first_name"]))
        if r.get("username"):
            user_parts.append(f"@{r['username']}")
        if not user_parts:
            user_parts.append(f"ID: {r['user_id']}")
        user_line = " ".join(user_parts)
        parts.append(
            f"\n{'—' * 20}\n"
            f"👤 {user_line}\n"
            f"🗺 {route_safe}\n"
            f"⭐ {stars} ({r['rating']}/5)\n"
        )
        if r.get("text") and str(r["text"]).strip():
            parts.append(f"💬 {html.escape(str(r['text']))}\n")
        else:
            parts.append("💬 (без текста)\n")
    message = "".join(parts)
    max_len = 4090
    if len(message) <= max_len:
        messages_to_send = [message]
    else:
        sep = "\n" + "—" * 20 + "\n"
        blocks = message.split(sep)
        messages_to_send = []
        current = blocks[0]
        for b in blocks[1:]:
            if len(current) + len(sep) + len(b) <= max_len:
                current += sep + b
            else:
                messages_to_send.append(current)
                current = b
        if current:
            messages_to_send.append(current)
        for j in range(1, len(messages_to_send)):
            messages_to_send[j] = "⭐ <b>Новые отзывы</b> (продолжение):\n" + messages_to_send[j]
    for admin_id in admin_ids:
        try:
            for msg in messages_to_send:
                await bot.send_message(admin_id, msg, parse_mode="HTML")
            logger.info("Отправлена сводка отзывов (%s шт.) админу %s", len(to_send), admin_id)
        except Exception as e:
            logger.error("Не удалось отправить сводку отзывов админу %s: %s", admin_id, e)
=======
    async def notify_new_review(
        self,
        user_id: int,
        username: str,
        route_name: str,
        rating: int,
        text: str = None
    ):
        stars = "⭐" * rating
        username_text = f"@{username}" if username else f"ID: {user_id}"
        message = (
            f"⭐ <b>Новый отзыв!</b>\n\n"
            f"👤 Пользователь: {username_text}\n"
            f"🗺 Маршрут: {route_name}\n"
            f"⭐ Оценка: {stars} ({rating}/5)\n"
        )
        if text:
            message += f"\n💬 Отзыв:\n{text}"
        for admin_id in self.admin_ids:
            try:
                await self.bot.send_message(admin_id, message, parse_mode="HTML")
            except Exception as e:
                logger.error(f"Не удалось отправить уведомление админу {admin_id}: {e}")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
