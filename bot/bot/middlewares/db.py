from typing import Callable, Dict, Any, Awaitable
from aiogram import BaseMiddleware
from aiogram.types import TelegramObject
from bot.loader import SessionLocal
class DbSessionMiddleware(BaseMiddleware):
    async def __call__(
        self,
        handler: Callable[[TelegramObject, Dict[str, Any]], Awaitable[Any]],
        event: TelegramObject,
        data: Dict[str, Any],
    ) -> Any:
        async with SessionLocal() as session:
            data["session"] = session
<<<<<<< HEAD
            try:
                return await handler(event, data)
            except Exception:
                await session.rollback()
                raise
=======
            return await handler(event, data)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
