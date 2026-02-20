from typing import List, Optional
from decimal import Decimal
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.quiz import QuizQuestion, QuizResult
class QuizRepository:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def get_questions_by_route(self, route_id: int) -> List[QuizQuestion]:
        result = await self.session.execute(
            select(QuizQuestion)
            .where(QuizQuestion.route_id == route_id)
            .order_by(QuizQuestion.order)
        )
        return list(result.scalars().all())
    async def has_result(self, progress_id: int) -> bool:
        result = await self.session.execute(
            select(QuizResult).where(QuizResult.progress_id == progress_id)
        )
        return result.scalars().first() is not None
    async def save_result(
        self,
        user_id: int,
        progress_id: int,
        route_id: int,
        correct_count: int,
        total_count: int,
        reward_given: Decimal,
    ) -> QuizResult:
        quiz_result = QuizResult(
            user_id=user_id,
            progress_id=progress_id,
            route_id=route_id,
            correct_count=correct_count,
            total_count=total_count,
            reward_given=reward_given,
        )
        self.session.add(quiz_result)
        await self.session.commit()
        await self.session.refresh(quiz_result)
        return quiz_result