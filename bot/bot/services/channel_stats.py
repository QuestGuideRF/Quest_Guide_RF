import asyncio
import json
import logging
import re
from pathlib import Path
from datetime import datetime, time, timedelta
from typing import Optional, Tuple
from sqlalchemy import text
logger = logging.getLogger(__name__)
STATS_FILE_NAME = "channel_stats_last.json"
HISTORY_DAYS = 31
MSK_UTC_OFFSET_HOURS = 3
DEFAULT_STATS_TIME_MSK = "08:00"
def _get_stats_path() -> Path:
    return Path(__file__).resolve().parent.parent.parent / STATS_FILE_NAME
def _load_stats_data() -> dict:
    p = _get_stats_path()
    if not p.exists():
        return {}
    try:
        with open(p, "r", encoding="utf-8") as f:
            data = json.load(f)
        if not data.get("history") and data.get("date") and data.get("member_count") is not None:
            data["history"] = [{"date": data["date"], "count": data["member_count"]}]
        return data
    except Exception as e:
        logger.warning("Не удалось прочитать channel_stats_last.json: %s", e)
        return {}
def _load_last_stats() -> Tuple[Optional[int], Optional[str]]:
    data = _load_stats_data()
    return data.get("member_count"), data.get("date")
def _save_stats_with_history(member_count: int) -> None:
    p = _get_stats_path()
    today = datetime.now().date().isoformat()
    data = _load_stats_data()
    history = data.get("history") or []
    history.append({"date": today, "count": member_count})
    by_date = {h["date"]: h["count"] for h in history}
    unique = [{"date": d, "count": by_date[d]} for d in sorted(by_date.keys(), reverse=True)[:HISTORY_DAYS]]
    try:
        with open(p, "w", encoding="utf-8") as f:
            json.dump({
                "member_count": member_count,
                "date": today,
                "history": unique,
            }, f, ensure_ascii=False)
    except Exception as e:
        logger.warning("Не удалось записать channel_stats_last.json: %s", e)
def _parse_time_msk(time_str: str) -> Tuple[int, int]:
    time_str = (time_str or "").strip()[:5]
    m = re.match(r"^(\d{1,2}):(\d{2})$", time_str)
    if not m:
        return 5, 0
    h, mi = int(m.group(1)), int(m.group(2))
    h = max(0, min(23, h))
    mi = max(0, min(59, mi))
    hour_utc = (h - MSK_UTC_OFFSET_HOURS) % 24
    return hour_utc, mi
def _next_run_utc_from_time_msk(time_str: str) -> datetime:
    hour_utc, minute = _parse_time_msk(time_str)
    now = datetime.utcnow()
    target = now.replace(hour=hour_utc, minute=minute, second=0, microsecond=0)
    if now >= target:
        target += timedelta(days=1)
    return target
async def get_channel_stats_settings(engine) -> Tuple[bool, str]:
    try:
        async with engine.connect() as conn:
            r = await conn.execute(text("SELECT value FROM system_settings WHERE `key` = 'channel_stats_enabled'"))
            row = r.fetchone()
            enabled = row[0] == "1" if row else True
            r2 = await conn.execute(text("SELECT value FROM system_settings WHERE `key` = 'channel_stats_time'"))
            row2 = r2.fetchone()
            time_str = (row2[0] or DEFAULT_STATS_TIME_MSK).strip() if row2 else DEFAULT_STATS_TIME_MSK
        return enabled, time_str
    except Exception as e:
        logger.warning("Не удалось прочитать настройки статистики канала: %s", e)
        return True, DEFAULT_STATS_TIME_MSK
def _format_diff(value: int) -> str:
    if value >= 0:
        return f"+{value}"
    return str(value)
async def _send_channel_stats_once(bot, channel_id_or_username, admin_ids: list) -> None:
    if not channel_id_or_username or not admin_ids:
        return
    try:
        count = await bot.get_chat_member_count(chat_id=channel_id_or_username)
    except Exception as e:
        logger.warning("Не удалось получить количество подписчиков канала: %s", e)
        for admin_id in admin_ids:
            try:
                await bot.send_message(
                    admin_id,
                    f"📊 <b>Статистика канала</b>\n\n"
                    f"❌ Не удалось получить число подписчиков: {str(e)[:200]}",
                    parse_mode="HTML",
                )
            except Exception as send_err:
                logger.warning("Не удалось отправить сообщение админу %s: %s", admin_id, send_err)
        return
    data = _load_stats_data()
    history = data.get("history") or []
    by_date = {h["date"]: h["count"] for h in history}
    today_iso = datetime.now().date().isoformat()
    yesterday = (datetime.now().date() - timedelta(days=1)).isoformat()
    week_ago = (datetime.now().date() - timedelta(days=7)).isoformat()
    month_ago = (datetime.now().date() - timedelta(days=30)).isoformat()
    count_yesterday = by_date.get(yesterday)
    count_week_ago = by_date.get(week_ago)
    count_month_ago = by_date.get(month_ago)
    _save_stats_with_history(count)
    channel_label = str(channel_id_or_username)
    if isinstance(channel_id_or_username, str) and channel_label.startswith("@"):
        channel_label = channel_label
    else:
        channel_label = f"ID {channel_id_or_username}"
    msg = (
        f"📊 <b>Статистика канала</b> ({channel_label})\n\n"
        f"👥 <b>Сейчас подписчиков:</b> {count}\n"
    )
    if count_yesterday is not None:
        diff_day = count - count_yesterday
        msg += f"📅 Вчера было: {count_yesterday}\n"
        msg += f"📈 За сутки: {_format_diff(diff_day)}\n"
    else:
        last_count = data.get("member_count")
        if last_count is not None and last_count != count:
            msg += f"📈 Изменение с прошлого отчёта: {_format_diff(count - last_count)}\n"
    if count_week_ago is not None:
        msg += f"📈 За 7 дней: {_format_diff(count - count_week_ago)}\n"
    if count_month_ago is not None:
        msg += f"📈 За 30 дней: {_format_diff(count - count_month_ago)}\n"
    msg += f"\n🕐 Обновлено: {datetime.now().strftime('%d.%m.%Y %H:%M')} (МСК)"
    for admin_id in admin_ids:
        try:
            await bot.send_message(admin_id, msg, parse_mode="HTML")
        except Exception as e:
            logger.warning("Не удалось отправить статистику канала админу %s: %s", admin_id, e)
    logger.info("Отправлена статистика канала: подписчиков=%s", count)
async def run_daily_channel_stats(
    bot,
    channel_id: Optional[int],
    channel_username: Optional[str],
    admin_ids: list,
    engine,
) -> None:
    channel = channel_id or channel_username
    if not channel:
        logger.info("Канал не настроен, ежедневная статистика канала отключена")
        return
    if channel_username:
        chat_id_for_count = f"@{channel_username}" if not str(channel_username).startswith("@") else channel_username
    elif channel_id:
        cid = channel_id
        if cid > 0:
            cid = -1000000000000 - cid
        chat_id_for_count = cid
    else:
        chat_id_for_count = None
    if not chat_id_for_count:
        logger.warning("Невозможно получить статистику: нет ни channel_id, ни channel_username")
        return
    logger.info("Запущена ежедневная статистика канала (время и вкл/выкл из system_settings)")
    while True:
        try:
            enabled, time_str = await get_channel_stats_settings(engine)
            if not enabled:
                logger.debug("Отправка статистики канала выключена в настройках, ждём 5 мин")
                await asyncio.sleep(300)
                continue
            if not admin_ids:
                await asyncio.sleep(3600)
                continue
            now_utc = datetime.utcnow()
            next_run = _next_run_utc_from_time_msk(time_str)
            wait_seconds = (next_run - now_utc).total_seconds()
            if wait_seconds < 0:
                wait_seconds = 0
            if wait_seconds > 0:
                logger.debug("Следующая отправка статистики канала через %.0f с (в %s МСК)", wait_seconds, time_str)
                await asyncio.sleep(wait_seconds)
            enabled2, _ = await get_channel_stats_settings(engine)
            if not enabled2:
                continue
            await _send_channel_stats_once(bot, chat_id_for_count, admin_ids)
            await asyncio.sleep(60)
        except asyncio.CancelledError:
            logger.info("Ежедневная статистика канала остановлена")
            raise
        except Exception as e:
            logger.exception("Ошибка в задаче статистики канала: %s", e)
            await asyncio.sleep(3600)