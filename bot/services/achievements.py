<<<<<<< HEAD
from datetime import datetime
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, func, text
from bot.models.achievement import Achievement, UserAchievement
from bot.models.user_progress import UserProgress, ProgressStatus
from bot.models.route import Route
class AchievementService:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def check_and_grant_achievements(self, user_id: int) -> list[Achievement]:
        result = await self.session.execute(select(Achievement))
        all_achievements = result.scalars().all()
        result = await self.session.execute(
            select(UserAchievement.achievement_id).where(
                UserAchievement.user_id == user_id
=======
from datetime import datetime, time as datetime_time
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, func
from bot.models.achievement import Achievement, UserAchievement
from bot.models.user_progress import UserProgress, ProgressStatus
from bot.models.payment import Payment, PaymentStatus
class AchievementService:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def check_and_grant_achievements(self, telegram_id: int) -> list[Achievement]:
        result = await self.session.execute(
            select(Achievement)
        )
        all_achievements = result.scalars().all()
        result = await self.session.execute(
            select(UserAchievement.achievement_id).where(
                UserAchievement.user_id == telegram_id
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            )
        )
        earned_ids = {row[0] for row in result.fetchall()}
        new_achievements = []
        for achievement in all_achievements:
            if achievement.id in earned_ids:
                continue
<<<<<<< HEAD
            if await self._check_condition(user_id, achievement):
                await self._grant_achievement(user_id, achievement.id)
                new_achievements.append(achievement)
        return new_achievements
    async def _check_condition(self, user_id: int, achievement: Achievement) -> bool:
        condition_type = achievement.condition_type
        condition_value = achievement.condition_value
        if condition_type == 'routes_completed':
            return await self._check_routes_completed(user_id, condition_value)
        elif condition_type == 'points_completed':
            return await self._check_points_completed(user_id, condition_value)
        elif condition_type == 'photos_taken':
            return await self._check_photos_taken(user_id, condition_value)
        elif condition_type == 'perfect_route':
            return await self._check_perfect_route(user_id)
        elif condition_type == 'fast_completion':
            return await self._check_fast_completion(user_id)
        elif condition_type == 'night_quest':
            return await self._check_night_quest(user_id)
        elif condition_type == 'early_bird':
            return await self._check_early_bird(user_id)
        elif condition_type == 'all_achievements':
            return await self._check_all_achievements(user_id, condition_value)
        return False
    async def _check_routes_completed(self, user_id: int, count: int) -> bool:
        result = await self.session.execute(
            select(func.count(UserProgress.id)).where(
                UserProgress.user_id == user_id,
                UserProgress.status == ProgressStatus.COMPLETED
            )
        )
        completed_count = result.scalar() or 0
        return completed_count >= count
    async def _check_points_completed(self, user_id: int, count: int) -> bool:
        result = await self.session.execute(
            select(func.sum(UserProgress.points_completed)).where(
                UserProgress.user_id == user_id
=======
            if await self._check_condition(telegram_id, achievement):
                await self._grant_achievement(telegram_id, achievement.id)
                new_achievements.append(achievement)
        return new_achievements
    async def _check_condition(self, telegram_id: int, achievement: Achievement) -> bool:
        condition_type = achievement.condition_type
        condition_value = achievement.condition_value
        if condition_type == 'routes_completed':
            return await self._check_routes_completed(telegram_id, condition_value)
        elif condition_type == 'points_completed':
            return await self._check_points_completed(telegram_id, condition_value)
        elif condition_type == 'photos_taken':
            return await self._check_photos_taken(telegram_id, condition_value)
        elif condition_type == 'perfect_route':
            return await self._check_perfect_route(telegram_id)
        elif condition_type == 'fast_completion':
            return await self._check_fast_completion(telegram_id)
        elif condition_type == 'night_quest':
            return await self._check_night_quest(telegram_id)
        elif condition_type == 'early_bird':
            return await self._check_early_bird(telegram_id)
        elif condition_type == 'all_achievements':
            return await self._check_all_achievements(telegram_id, condition_value)
        return False
    async def _check_routes_completed(self, telegram_id: int, count: int) -> bool:
        result = await self.session.execute(
            select(func.count(UserProgress.id)).where(
                UserProgress.user_id == telegram_id,
                UserProgress.status == ProgressStatus.COMPLETED
            )
        )
        completed_count = result.scalar()
        return completed_count >= count
    async def _check_points_completed(self, telegram_id: int, count: int) -> bool:
        result = await self.session.execute(
            select(func.sum(UserProgress.points_completed)).where(
                UserProgress.user_id == telegram_id
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            )
        )
        total_points = result.scalar() or 0
        return total_points >= count
<<<<<<< HEAD
    async def _check_photos_taken(self, user_id: int, count: int) -> bool:
        result = await self.session.execute(
            text("SELECT COUNT(*) FROM user_photos WHERE user_id = :user_id"),
            {"user_id": user_id}
        )
        row = result.fetchone()
        photos_count = row[0] if row else 0
        return photos_count >= count
    async def _check_perfect_route(self, user_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress).where(
                UserProgress.user_id == user_id,
=======
    async def _check_photos_taken(self, telegram_id: int, count: int) -> bool:
        result = await self.session.execute(
            f"SELECT COUNT(*) FROM user_photos WHERE user_id = {telegram_id}"
        )
        photos_count = result.scalar()
        return photos_count >= count
    async def _check_perfect_route(self, telegram_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress).where(
                UserProgress.user_id == telegram_id,
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                UserProgress.status == ProgressStatus.COMPLETED
            )
        )
        progresses = result.scalars().all()
        for progress in progresses:
<<<<<<< HEAD
            cnt_result = await self.session.execute(
                text("SELECT COUNT(*) FROM points WHERE route_id = :route_id"),
                {"route_id": progress.route_id}
            )
            row = cnt_result.fetchone()
            total_points = row[0] if row else 0
            if progress.points_completed >= total_points:
                return True
        return False
    async def _check_fast_completion(self, user_id: int) -> bool:
        result = await self.session.execute(
            select(
                UserProgress.started_at,
                UserProgress.completed_at,
                Route.duration_minutes,
            )
            .join(Route, UserProgress.route_id == Route.id)
            .where(
                UserProgress.user_id == user_id,
                UserProgress.status == ProgressStatus.COMPLETED,
                UserProgress.completed_at.isnot(None),
            )
        )
        for row in result.fetchall():
            started_at, completed_at, duration_minutes = row
            if started_at is None or completed_at is None:
                continue
            actual_duration = (completed_at - started_at).total_seconds() / 60
            if duration_minutes is not None and actual_duration < duration_minutes:
                return True
        return False
    async def _check_night_quest(self, user_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress.started_at).where(
                UserProgress.user_id == user_id,
=======
            result = await self.session.execute(
                f"SELECT COUNT(*) FROM points WHERE route_id = {progress.route_id}"
            )
            total_points = result.scalar()
            if progress.points_completed >= total_points:
                return True
        return False
    async def _check_fast_completion(self, telegram_id: int) -> bool:
        result = await self.session.execute(
            f
        )
        progresses = result.fetchall()
        for progress in progresses:
            started_at, completed_at, estimated_duration = progress
            actual_duration = (completed_at - started_at).total_seconds() / 60
            if estimated_duration and actual_duration < estimated_duration:
                return True
        return False
    async def _check_night_quest(self, telegram_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress.started_at).where(
                UserProgress.user_id == telegram_id,
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                UserProgress.status == ProgressStatus.COMPLETED
            )
        )
        for row in result.fetchall():
            started_at = row[0]
<<<<<<< HEAD
            if started_at:
                hour = started_at.hour
                if hour >= 22 or hour < 6:
                    return True
        return False
    async def _check_early_bird(self, user_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress.started_at).where(
                UserProgress.user_id == user_id
=======
            hour = started_at.hour
            if hour >= 22 or hour < 6:
                return True
        return False
    async def _check_early_bird(self, telegram_id: int) -> bool:
        result = await self.session.execute(
            select(UserProgress.started_at).where(
                UserProgress.user_id == telegram_id
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            )
        )
        for row in result.fetchall():
            started_at = row[0]
<<<<<<< HEAD
            if started_at and started_at.hour < 8:
                return True
        return False
    async def _check_all_achievements(self, user_id: int, required_count: int) -> bool:
        result = await self.session.execute(
            select(func.count(UserAchievement.id)).where(
                UserAchievement.user_id == user_id
            )
        )
        earned_count = result.scalar() or 0
        return earned_count >= required_count
    async def _grant_achievement(self, user_id: int, achievement_id: int):
        user_achievement = UserAchievement(
            user_id=user_id,
=======
            if started_at.hour < 8:
                return True
        return False
    async def _check_all_achievements(self, telegram_id: int, required_count: int) -> bool:
        result = await self.session.execute(
            select(func.count(UserAchievement.id)).where(
                UserAchievement.user_id == telegram_id
            )
        )
        earned_count = result.scalar()
        return earned_count >= required_count
    async def _grant_achievement(self, telegram_id: int, achievement_id: int):
        user_achievement = UserAchievement(
            user_id=telegram_id,
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            achievement_id=achievement_id,
            earned_at=datetime.utcnow()
        )
        self.session.add(user_achievement)