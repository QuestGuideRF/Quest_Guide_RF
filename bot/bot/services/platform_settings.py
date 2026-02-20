import time
from typing import Optional, Dict, Tuple, Any
from decimal import Decimal
from sqlalchemy import select, text
from sqlalchemy.ext.asyncio import AsyncSession
_CACHE_TTL_SEC = 60
_platform_cache: Dict[str, Tuple[Any, float]] = {}
def _cache_cleanup(cache: dict, ttl: float) -> None:
    now = time.monotonic()
    for k in list(cache.keys()):
        if now - cache[k][1] > ttl:
            del cache[k]
class PlatformSettingsService:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def get_setting(self, key: str, default: str = None) -> Optional[str]:
        now = time.monotonic()
        _cache_cleanup(_platform_cache, _CACHE_TTL_SEC)
        if key in _platform_cache and (now - _platform_cache[key][1]) < _CACHE_TTL_SEC:
            return _platform_cache[key][0]
        result = await self.session.execute(
            text("SELECT value FROM platform_settings WHERE `key` = :key"),
            {"key": key}
        )
        row = result.fetchone()
        value = row[0] if row else default
        _platform_cache[key] = (value, now)
        return value
    async def get_setting_int(self, key: str, default: int = 0) -> int:
        value = await self.get_setting(key)
        try:
            return int(value) if value else default
        except (ValueError, TypeError):
            return default
    async def get_setting_decimal(self, key: str, default: Decimal = Decimal("0")) -> Decimal:
        value = await self.get_setting(key)
        try:
            return Decimal(value) if value else default
        except (ValueError, TypeError):
            return default
    async def get_setting_bool(self, key: str, default: bool = False) -> bool:
        value = await self.get_setting(key)
        if value is None:
            return default
        return value.lower() in ("1", "true", "yes", "on")
    async def set_setting(self, key: str, value: str, description: str = None):
        existing = await self.get_setting(key)
        if existing is not None:
            await self.session.execute(
                text("UPDATE platform_settings SET value = :value WHERE `key` = :key"),
                {"key": key, "value": value}
            )
        else:
            if description:
                await self.session.execute(
                    text("INSERT INTO platform_settings (`key`, value, description) VALUES (:key, :value, :description)"),
                    {"key": key, "value": value, "description": description}
                )
            else:
                await self.session.execute(
                    text("INSERT INTO platform_settings (`key`, value) VALUES (:key, :value)"),
                    {"key": key, "value": value}
                )
        await self.session.commit()
        if key in _platform_cache:
            del _platform_cache[key]
    async def get_review_reward_enabled(self) -> bool:
        return await self.get_setting_bool("review_reward_enabled", default=True)
    async def get_review_reward_amount(self) -> Decimal:
        return await self.get_setting_decimal("review_reward_amount", default=Decimal("10"))
    async def get_referral_level_tokens(self, level: int = 1) -> Decimal:
        return await self.get_setting_decimal(f"referral_level{level}_tokens", default=Decimal("20"))
    async def get_referral_level_discount(self, level: int = 2) -> int:
        return await self.get_setting_int(f"referral_level{level}_discount", default=15)
    async def get_referral_required(self, level: int) -> int:
        defaults = {1: 3, 2: 10, 3: 30, 4: 100}
        return await self.get_setting_int(f"referral_level{level}_required", default=defaults.get(level, 100))