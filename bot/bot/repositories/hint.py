from typing import List, Optional
from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.hint import Hint
from bot.repositories.base import BaseRepository
class HintRepository(BaseRepository[Hint]):
    def __init__(self, session: AsyncSession):
        super().__init__(Hint, session)
    async def get_by_point(self, point_id: int) -> List[Hint]:
        result = await self.session.execute(
            select(Hint)
            .where(Hint.point_id == point_id)
            .order_by(Hint.order, Hint.level)
        )
        return list(result.scalars().all())
    async def get_by_point_and_level(self, point_id: int, level: int) -> Optional[Hint]:
        result = await self.session.execute(
            select(Hint)
            .where(Hint.point_id == point_id, Hint.level == level)
            .order_by(Hint.order)
            .limit(1)
        )
        return result.scalar_one_or_none()
    async def get_available_levels(self, point_id: int) -> List[int]:
        result = await self.session.execute(
            select(Hint.level.distinct())
            .where(Hint.point_id == point_id)
            .order_by(Hint.level)
        )
        return [row[0] for row in result.all()]
    async def count_by_point(self, point_id: int) -> int:
        result = await self.session.execute(
            select(func.count(Hint.id)).where(Hint.point_id == point_id)
        )
        return result.scalar() or 0