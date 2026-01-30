import logging
from typing import Optional, Tuple, List
from sqlalchemy.ext.asyncio import AsyncSession
from bot.repositories.hint import HintRepository
from bot.repositories.user_hint import UserHintRepository
from bot.repositories.route import RouteRepository
from bot.models.hint import Hint
logger = logging.getLogger(__name__)
class HintService:
    def __init__(self, session: AsyncSession):
        self.session = session
        self.hint_repo = HintRepository(session)
        self.user_hint_repo = UserHintRepository(session)
        self.route_repo = RouteRepository(session)
    async def check_hint_availability(
        self, user_id: int, route_id: int, point_id: int
    ) -> Tuple[bool, str, int, int]:
        route = await self.route_repo.get(route_id)
        if not route:
            return False, "Маршрут не найден", 0, 0
        max_hints = route.max_hints_per_route
        hints_used = await self.user_hint_repo.count_user_route_hints(user_id, route_id)
        if hints_used >= max_hints:
            return False, f"Вы использовали все подсказки ({max_hints}/{max_hints})", hints_used, max_hints
        hints_count = await self.hint_repo.count_by_point(point_id)
        if hints_count == 0:
            return False, "Для этой точки нет подсказок", hints_used, max_hints
        return True, "", hints_used, max_hints
    async def get_available_hints(
        self, user_id: int, point_id: int
    ) -> List[Hint]:
        all_hints = await self.hint_repo.get_by_point(point_id)
        used_levels = await self.user_hint_repo.get_used_hint_levels(user_id, point_id)
        available = [hint for hint in all_hints if hint.level not in used_levels]
        return available
    async def get_next_hint_level(
        self, user_id: int, point_id: int
    ) -> Optional[int]:
        used_levels = await self.user_hint_repo.get_used_hint_levels(user_id, point_id)
        available_levels = await self.hint_repo.get_available_levels(point_id)
        unused_levels = [level for level in available_levels if level not in used_levels]
        return min(unused_levels) if unused_levels else None
    async def use_hint(
        self, user_id: int, route_id: int, point_id: int, hint_level: int
    ) -> Optional[Hint]:
        hint = await self.hint_repo.get_by_point_and_level(point_id, hint_level)
        if not hint:
            logger.warning(f"Hint not found: point_id={point_id}, level={hint_level}")
            return None
        is_used = await self.user_hint_repo.is_hint_used(user_id, hint.id)
        if is_used:
            logger.warning(f"Hint already used: user_id={user_id}, hint_id={hint.id}")
            return None
        await self.user_hint_repo.use_hint(user_id, route_id, point_id, hint.id)
        logger.info(f"Hint used: user_id={user_id}, point_id={point_id}, hint_id={hint.id}, level={hint_level}")
        return hint
    async def get_hints_stats(self, user_id: int, route_id: int) -> dict:
        route = await self.route_repo.get(route_id)
        if not route:
            return {"used": 0, "max": 0, "remaining": 0}
        used = await self.user_hint_repo.count_user_route_hints(user_id, route_id)
        max_hints = route.max_hints_per_route
        return {
            "used": used,
            "max": max_hints,
            "remaining": max_hints - used,
        }