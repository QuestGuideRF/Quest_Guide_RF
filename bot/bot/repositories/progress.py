from datetime import datetime
<<<<<<< HEAD
from typing import Optional, List
from sqlalchemy import select, or_
=======
from typing import Optional
from sqlalchemy import select
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user_progress import UserProgress, ProgressStatus
from bot.repositories.base import BaseRepository
class ProgressRepository(BaseRepository[UserProgress]):
    def __init__(self, session: AsyncSession):
        super().__init__(UserProgress, session)
    async def get_active_progress(
        self,
        user_id: int,
        route_id: int,
    ) -> Optional[UserProgress]:
        result = await self.session.execute(
            select(UserProgress).where(
                UserProgress.user_id == user_id,
                UserProgress.route_id == route_id,
                UserProgress.status == ProgressStatus.IN_PROGRESS,
            ).order_by(UserProgress.started_at.desc()).limit(1)
        )
        return result.scalar_one_or_none()
<<<<<<< HEAD
    async def get_active_or_paused_progress(
        self,
        user_id: int,
        route_id: int = None,
    ) -> Optional[UserProgress]:
        query = select(UserProgress).where(
            UserProgress.user_id == user_id,
            or_(
                UserProgress.status == ProgressStatus.IN_PROGRESS,
                UserProgress.status == ProgressStatus.PAUSED
            )
        )
        if route_id:
            query = query.where(UserProgress.route_id == route_id)
        query = query.order_by(UserProgress.started_at.desc()).limit(1)
        result = await self.session.execute(query)
        return result.scalar_one_or_none()
    async def get_paused_progress(self, user_id: int) -> Optional[UserProgress]:
        result = await self.session.execute(
            select(UserProgress).where(
                UserProgress.user_id == user_id,
                UserProgress.status == ProgressStatus.PAUSED,
            ).order_by(UserProgress.started_at.desc()).limit(1)
        )
        return result.scalar_one_or_none()
    async def get_all_active_progresses(self, user_id: int) -> List[UserProgress]:
        result = await self.session.execute(
            select(UserProgress).where(
                UserProgress.user_id == user_id,
                or_(
                    UserProgress.status == ProgressStatus.IN_PROGRESS,
                    UserProgress.status == ProgressStatus.PAUSED
                )
            ).order_by(UserProgress.started_at.desc())
        )
        return list(result.scalars().all())
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    async def start_route(
        self,
        user_id: int,
        route_id: int,
        first_point_id: int,
    ) -> UserProgress:
        progress = await self.create(
            user_id=user_id,
            route_id=route_id,
            current_point_id=first_point_id,
            current_point_order=0,
            status=ProgressStatus.IN_PROGRESS,
            started_at=datetime.utcnow(),
            points_completed=0,
        )
        return progress
    async def complete_point(
        self,
        progress: UserProgress,
        next_point_id: Optional[int] = None,
        next_order: Optional[int] = None,
    ) -> UserProgress:
        progress.points_completed += 1
        if next_point_id:
            progress.current_point_id = next_point_id
            progress.current_point_order = next_order or (progress.current_point_order + 1)
        else:
            progress.status = ProgressStatus.COMPLETED
            progress.completed_at = datetime.utcnow()
        await self.session.commit()
        await self.session.refresh(progress)
        return progress
<<<<<<< HEAD
    async def pause_quest(self, progress: UserProgress) -> UserProgress:
        if progress.status != ProgressStatus.IN_PROGRESS:
            return progress
        progress.status = ProgressStatus.PAUSED
        progress.paused_at = datetime.utcnow()
        progress.is_paused = True
        await self.session.commit()
        await self.session.refresh(progress)
        return progress
    async def resume_quest(self, progress: UserProgress) -> UserProgress:
        if progress.status != ProgressStatus.PAUSED:
            return progress
        if progress.paused_at:
            paused_duration = int((datetime.utcnow() - progress.paused_at).total_seconds())
            progress.total_paused_seconds += paused_duration
        progress.status = ProgressStatus.IN_PROGRESS
        progress.paused_at = None
        progress.is_paused = False
        await self.session.commit()
        await self.session.refresh(progress)
        return progress
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    async def get_user_stats(self, user_id: int) -> dict:
        from sqlalchemy import text, func
        result = await self.session.execute(
            select(UserProgress).where(UserProgress.user_id == user_id)
        )
        progresses = list(result.scalars().all())
        photo_result = await self.session.execute(
            text("SELECT COUNT(*) as count FROM user_photos WHERE user_id = :user_id"),
            {"user_id": user_id}
        )
        total_photos = photo_result.scalar() or 0
        longest_result = await self.session.execute(
            select(func.max(
                func.timestampdiff(
                    text("MINUTE"),
                    UserProgress.started_at,
                    UserProgress.completed_at
                )
            ))
            .where(
                UserProgress.user_id == user_id,
                UserProgress.status == ProgressStatus.COMPLETED,
                UserProgress.completed_at.isnot(None)
            )
        )
        longest_quest = longest_result.scalar() or 0
        shortest_result = await self.session.execute(
            select(func.min(
                func.timestampdiff(
                    text("MINUTE"),
                    UserProgress.started_at,
                    UserProgress.completed_at
                )
            ))
            .where(
                UserProgress.user_id == user_id,
                UserProgress.status == ProgressStatus.COMPLETED,
                UserProgress.completed_at.isnot(None),
                func.timestampdiff(
                    text("MINUTE"),
                    UserProgress.started_at,
                    UserProgress.completed_at
                ) > 0
            )
        )
        shortest_quest = shortest_result.scalar() or 0
        user_rank = 1
        completed_count = sum(1 for p in progresses if p.status == ProgressStatus.COMPLETED)
        if completed_count > 0:
<<<<<<< HEAD
            try:
                rank_result = await self.session.execute(
                    text("""
                        SELECT COUNT(*) + 1 as user_rank
                        FROM (
                            SELECT user_id, COUNT(*) as completed_routes
                            FROM user_progress
                            WHERE status = 'COMPLETED'
                            GROUP BY user_id
                            HAVING COUNT(*) > :completed_count
                        ) as better_users
                    """),
                    {"completed_count": completed_count}
                )
                user_rank = rank_result.scalar() or 1
            except Exception:
                user_rank = 1
=======
            rank_result = await self.session.execute(
                text(),
                {"completed_count": completed_count}
            )
            user_rank = rank_result.scalar() or 1
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        return {
            "total_routes": len(progresses),
            "completed": sum(1 for p in progresses if p.status == ProgressStatus.COMPLETED),
            "in_progress": sum(1 for p in progresses if p.status == ProgressStatus.IN_PROGRESS),
            "total_points": sum(p.points_completed for p in progresses),
            "total_photos": total_photos,
            "longest_quest": longest_quest,
            "shortest_quest": shortest_quest,
            "user_rank": user_rank,
        }