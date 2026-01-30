from typing import List, Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload
from bot.models.route import Route
from bot.repositories.base import BaseRepository
from bot.repositories.route_filter_mixin import RouteFilterMixin
class RouteRepository(BaseRepository[Route], RouteFilterMixin):
    def __init__(self, session: AsyncSession):
        super().__init__(Route, session)
    async def get_with_points(self, route_id: int) -> Optional[Route]:
        result = await self.session.execute(
            select(Route)
            .options(selectinload(Route.points))
            .where(Route.id == route_id)
        )
        return result.scalar_one_or_none()
    async def get_average_completion_time(self, route_id: int) -> Optional[int]:
        from sqlalchemy import func, text
        from bot.models.user_progress import UserProgress, ProgressStatus
        result = await self.session.execute(
            select(func.avg(
                func.timestampdiff(
                    text("MINUTE"),
                    UserProgress.started_at,
                    UserProgress.completed_at
                )
            ))
            .where(
                UserProgress.route_id == route_id,
                UserProgress.status == ProgressStatus.COMPLETED,
                UserProgress.completed_at.isnot(None)
            )
        )
        avg_time = result.scalar()
        return int(avg_time) if avg_time else None
    async def get_route_with_tags(self, route_id: int) -> tuple:
        from bot.models.route_tag import RouteTag
        result = await self.session.execute(
            select(Route)
            .options(
                selectinload(Route.points),
                selectinload(Route.route_tags).selectinload(RouteTag.tag)
            )
            .where(Route.id == route_id)
        )
        route = result.scalar_one_or_none()
        if not route:
            return None, []
        tags = [rt.tag for rt in route.route_tags]
        return route, tags
    async def get_by_city(self, city_id: int, active_only: bool = True) -> List[Route]:
        query = select(Route).where(Route.city_id == city_id)
        if active_only:
            query = query.where(Route.is_active == True)
        query = query.order_by(Route.order)
        result = await self.session.execute(query)
        return list(result.scalars().all())
    async def get_active(self) -> List[Route]:
        result = await self.session.execute(
            select(Route).where(Route.is_active == True).order_by(Route.order)
        )
        return list(result.scalars().all())
    async def get_top_routes(self, limit: int = 10) -> List[tuple]:
        from sqlalchemy import func, text
        from bot.models.user_progress import UserProgress, ProgressStatus
        result = await self.session.execute(
            select(
                Route,
                func.count(UserProgress.id).label('completions'),
                func.avg(
                    func.timestampdiff(
                        text("MINUTE"),
                        UserProgress.started_at,
                        UserProgress.completed_at
                    )
                ).label('avg_time')
            )
            .join(UserProgress, UserProgress.route_id == Route.id)
            .where(
                Route.is_active == True,
                UserProgress.status == ProgressStatus.COMPLETED,
                UserProgress.completed_at.isnot(None)
            )
            .group_by(Route.id)
            .order_by(text('completions DESC'))
            .limit(limit)
        )
        rows = result.all()
        return [(row[0], row[1], int(row[2]) if row[2] else 0) for row in rows]