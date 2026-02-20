import time
import logging
from typing import Callable, Dict, Any, Awaitable
from aiogram import BaseMiddleware
from aiogram.types import TelegramObject, CallbackQuery
logger = logging.getLogger(__name__)
DEBOUNCE_SEC = 1
_debounce_cache: Dict[tuple, float] = {}
class DebounceCallbackMiddleware(BaseMiddleware):
    def __init__(self, cooldown_sec: float = DEBOUNCE_SEC):
        self.cooldown_sec = cooldown_sec
    async def __call__(
        self,
        handler: Callable[[TelegramObject, Dict[str, Any]], Awaitable[Any]],
        event: TelegramObject,
        data: Dict[str, Any],
    ) -> Any:
        if not isinstance(event, CallbackQuery):
            return await handler(event, data)
        user_id = event.from_user.id if event.from_user else 0
        callback_data = event.data or ""
        key = (user_id, callback_data)
        now = time.monotonic()
        for k in list(_debounce_cache.keys()):
            if now - _debounce_cache[k] > self.cooldown_sec:
                del _debounce_cache[k]
        if key in _debounce_cache and (now - _debounce_cache[key]) < self.cooldown_sec:
            try:
                await event.answer()
            except Exception as e:
                logger.debug("debounce: answer() failed: %s", e)
            return
        _debounce_cache[key] = now
        return await handler(event, data)