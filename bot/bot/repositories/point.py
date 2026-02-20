from typing import List, Optional
<<<<<<< HEAD
from collections import namedtuple
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload
from bot.models.point import Point
<<<<<<< HEAD
from bot.models.task import Task
from bot.repositories.base import BaseRepository
NextPointData = namedtuple("NextPointData", ["id", "order", "name", "name_en"])
=======
from bot.repositories.base import BaseRepository
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        point = result.scalar_one_or_none()
        if point and (not point.tasks or len(point.tasks) == 0):
            task_result = await self.session.execute(
                select(Task).where(Task.point_id == point_id).order_by(Task.order)
            )
            point.tasks = list(task_result.scalars().all())
        return point
=======
        return result.scalar_one_or_none()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    async def get_with_tasks_and_images(self, point_id: int) -> Optional[Point]:
        result = await self.session.execute(
            select(Point)
            .options(
                selectinload(Point.tasks),
                selectinload(Point.reference_images)
            )
            .where(Point.id == point_id)
        )
<<<<<<< HEAD
        point = result.scalar_one_or_none()
        if point and (not point.tasks or len(point.tasks) == 0):
            task_result = await self.session.execute(
                select(Task).where(Task.point_id == point_id).order_by(Task.order)
            )
            point.tasks = list(task_result.scalars().all())
        return point
=======
        return result.scalar_one_or_none()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        return result.scalar_one_or_none()
    async def get_next_point_data(
        self, route_id: int, current_order: int
    ) -> Optional[NextPointData]:
        result = await self.session.execute(
            select(Point.id, Point.order, Point.name, Point.name_en)
            .where(Point.route_id == route_id, Point.order > current_order)
            .order_by(Point.order)
            .limit(1)
        )
        row = result.one_or_none()
        return NextPointData(row.id, row.order, row.name, row.name_en) if row else None
=======
        return result.scalar_one_or_none()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
