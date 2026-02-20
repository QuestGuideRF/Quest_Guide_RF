import time
from typing import Tuple, Any, Dict
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
import logging
logger = logging.getLogger(__name__)
_SETTINGS_CACHE_TTL_SEC = 60
_settings_cache: Dict[str, Tuple[Any, float]] = {}
def _settings_cache_cleanup() -> None:
    now = time.monotonic()
    for k in list(_settings_cache.keys()):
        if now - _settings_cache[k][1] > _SETTINGS_CACHE_TTL_SEC:
            del _settings_cache[k]
async def is_manual_photo_moderation_enabled(session: AsyncSession) -> bool:
    key = "manual_photo_moderation_enabled"
    now = time.monotonic()
    _settings_cache_cleanup()
    if key in _settings_cache and (now - _settings_cache[key][1]) < _SETTINGS_CACHE_TTL_SEC:
        return _settings_cache[key][0]
    try:
        result = await session.execute(
            text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
        )
        row = result.fetchone()
        value = row[0] == '1' or row[0].lower() == 'true' if row else False
        _settings_cache[key] = (value, now)
        return value
    except Exception as e:
        logger.error(f"Ошибка проверки настройки ручной модерации: {e}")
        return False
async def is_subscription_check_enabled(session: AsyncSession) -> bool:
    key = "subscription_check_enabled"
    now = time.monotonic()
    _settings_cache_cleanup()
    if key in _settings_cache and (now - _settings_cache[key][1]) < _SETTINGS_CACHE_TTL_SEC:
        return _settings_cache[key][0]
    try:
        result = await session.execute(
            text("SELECT value FROM system_settings WHERE `key` = 'subscription_check_enabled'")
        )
        row = result.fetchone()
        if row:
            value = row[0] == '1' or row[0].lower() == 'true'
        else:
            from bot.loader import config
            value = config.channel.require_subscription if hasattr(config.channel, 'require_subscription') else False
        _settings_cache[key] = (value, now)
        return value
    except Exception as e:
        logger.error(f"Ошибка проверки настройки проверки подписки: {e}")
        from bot.loader import config
        fallback = config.channel.require_subscription if hasattr(config.channel, 'require_subscription') else False
        return fallback
async def get_referral_reward_amount(session: AsyncSession) -> int:
    key = "referral_reward_amount"
    now = time.monotonic()
    _settings_cache_cleanup()
    if key in _settings_cache and (now - _settings_cache[key][1]) < _SETTINGS_CACHE_TTL_SEC:
        return _settings_cache[key][0]
    try:
        result = await session.execute(
            text("SELECT value FROM platform_settings WHERE `key` = 'referral_reward_amount'")
        )
        row = result.fetchone()
        if row and row[0] is not None:
            value = int(row[0])
        else:
            value = 10
        _settings_cache[key] = (value, now)
        return value
    except Exception as e:
        logger.error(f"Ошибка чтения referral_reward_amount: {e}")
        return 10