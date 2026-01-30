from typing import List
from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user_hint import UserHint
from bot.repositories.base import BaseRepository
class UserHintRepository(BaseRepository[UserHint]):
    def __init__(self, session: AsyncSession):
        super().__init__(UserHint, session)
    async def get_user_route_hints(self, user_id: int, route_id: int) -> List[UserHint]:
        result = await self.session.execute(
            select(UserHint)
            .where(UserHint.user_id == user_id, UserHint.route_id == route_id)
            .order_by(UserHint.used_at)
        )
        return list(result.scalars().all())
    async def count_user_route_hints(self, user_id: int, route_id: int) -> int:
        result = await self.session.execute(
            select(func.count(UserHint.id))
            .where(UserHint.user_id == user_id, UserHint.route_id == route_id)
        )
        return result.scalar() or 0
    async def count_user_point_hints(self, user_id: int, point_id: int) -> int:
        result = await self.session.execute(
            select(func.count(UserHint.id))
            .where(UserHint.user_id == user_id, UserHint.point_id == point_id)
        )
        return result.scalar() or 0
    async def is_hint_used(self, user_id: int, hint_id: int) -> bool:
        result = await self.session.execute(
            select(func.count(UserHint.id))
            .where(UserHint.user_id == user_id, UserHint.hint_id == hint_id)
        )
        count = result.scalar() or 0
        return count > 0
    async def use_hint(
        self, user_id: int, route_id: int, point_id: int, hint_id: int
    ) -> UserHint:
        user_hint = UserHint(
            user_id=user_id,
            route_id=route_id,
            point_id=point_id,
            hint_id=hint_id,
        )
        self.session.add(user_hint)
        await self.session.commit()
        await self.session.refresh(user_hint)
        return user_hint
    async def get_used_hint_levels(self, user_id: int, point_id: int) -> List[int]:
        result = await self.session.execute(
            select(UserHint)
            .join(UserHint.hint)
            .where(UserHint.user_id == user_id, UserHint.point_id == point_id)
        )
        user_hints = list(result.scalars().all())
        return [uh.hint.level for uh in user_hints if uh.hint]