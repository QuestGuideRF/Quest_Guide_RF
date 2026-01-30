from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
import logging
logger = logging.getLogger(__name__)
async def is_manual_photo_moderation_enabled(session: AsyncSession) -> bool:
    try:
        result = await session.execute(
            text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
        )
        row = result.fetchone()
        if row:
            return row[0] == '1' or row[0].lower() == 'true'
        return False
    except Exception as e:
        logger.error(f"Ошибка проверки настройки ручной модерации: {e}")
        return False
async def is_subscription_check_enabled(session: AsyncSession) -> bool:
    try:
        result = await session.execute(
            text("SELECT value FROM system_settings WHERE `key` = 'subscription_check_enabled'")
        )
        row = result.fetchone()
        if row:
            return row[0] == '1' or row[0].lower() == 'true'
        from bot.config import load_config
        config = load_config()
        return config.channel.require_subscription if hasattr(config.channel, 'require_subscription') else False
    except Exception as e:
        logger.error(f"Ошибка проверки настройки проверки подписки: {e}")
        from bot.config import load_config
        config = load_config()
        return config.channel.require_subscription if hasattr(config.channel, 'require_subscription') else False