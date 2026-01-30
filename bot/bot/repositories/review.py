from typing import List, Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.review import Review
from bot.repositories.base import BaseRepository
class ReviewRepository(BaseRepository[Review]):
    def __init__(self, session: AsyncSession):
        super().__init__(Review, session)
    async def get_by_progress(self, progress_id: int) -> Optional[Review]:
        result = await self.session.execute(
            select(Review).where(Review.progress_id == progress_id)
        )
        return result.scalar_one_or_none()
    async def get_by_route(self, route_id: int, limit: int = 50) -> List[Review]:
        result = await self.session.execute(
            select(Review)
            .where(Review.route_id == route_id)
            .order_by(Review.created_at.desc())
            .limit(limit)
        )
        return list(result.scalars().all())
    async def get_user_reviews(self, user_id: int) -> List[Review]:
        result = await self.session.execute(
            select(Review)
            .where(Review.user_id == user_id)
            .order_by(Review.created_at.desc())
        )
        return list(result.scalars().all())
    async def create_review(
        self,
        user_id: int,
        route_id: int,
        progress_id: int,
        rating: int,
        text: Optional[str] = None
    ) -> Review:
        review = await self.create(
            user_id=user_id,
            route_id=route_id,
            progress_id=progress_id,
            rating=rating,
            text=text
        )
        return review