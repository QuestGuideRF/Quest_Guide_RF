from typing import List, Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload
from bot.models.point import Point
from bot.repositories.base import BaseRepository
class PointRepository(BaseRepository[Point]):
    def __init__(self, session: AsyncSession):
        super().__init__(Point, session)
    async def get_with_images(self, point_id: int) -> Optional[Point]:
        result = await self.session.execute(
            select(Point)
            .options(selectinload(Point.reference_images))
            .where(Point.id == point_id)
        )
        return result.scalar_one_or_none()
    async def get_with_tasks(self, point_id: int) -> Optional[Point]:
        result = await self.session.execute(
            select(Point)
            .options(selectinload(Point.tasks))
            .where(Point.id == point_id)
        )
        return result.scalar_one_or_none()
    async def get_with_tasks_and_images(self, point_id: int) -> Optional[Point]:
        result = await self.session.execute(
            select(Point)
            .options(
                selectinload(Point.tasks),
                selectinload(Point.reference_images)
            )
            .where(Point.id == point_id)
        )
        return result.scalar_one_or_none()
    async def get_by_route(self, route_id: int) -> List[Point]:
        result = await self.session.execute(
            select(Point)
            .where(Point.route_id == route_id)
            .order_by(Point.order)
        )
        return list(result.scalars().all())
    async def get_next_point(self, route_id: int, current_order: int) -> Optional[Point]:
        result = await self.session.execute(
            select(Point)
            .where(Point.route_id == route_id, Point.order > current_order)
            .order_by(Point.order)
            .limit(1)
        )
        return result.scalar_one_or_none()