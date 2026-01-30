from typing import List, Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload
from bot.models.task import Task
from bot.repositories.base import BaseRepository
class TaskRepository(BaseRepository[Task]):
    def __init__(self, session: AsyncSession):
        super().__init__(Task, session)
    async def get_by_point(self, point_id: int) -> List[Task]:
        result = await self.session.execute(
            select(Task)
            .where(Task.point_id == point_id)
            .order_by(Task.order)
        )
        return list(result.scalars().all())
    async def get_by_point_with_index(self, point_id: int, task_index: int) -> Optional[Task]:
        result = await self.session.execute(
            select(Task)
            .where(Task.point_id == point_id)
            .order_by(Task.order)
            .offset(task_index)
            .limit(1)
        )
        return result.scalar_one_or_none()