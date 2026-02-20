from typing import Optional
from decimal import Decimal
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.survey import SurveyResult
class SurveyRepository:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def has_result(self, progress_id: int) -> bool:
        result = await self.session.execute(
            select(SurveyResult).where(SurveyResult.progress_id == progress_id)
        )
        return result.scalars().first() is not None
    async def save_result(
        self,
        user_id: int,
        progress_id: int,
        route_id: int,
        answers: dict,
        reward_given: Decimal,
    ) -> SurveyResult:
        survey_result = SurveyResult(
            user_id=user_id,
            progress_id=progress_id,
            route_id=route_id,
            answers=answers,
            reward_given=reward_given,
        )
        self.session.add(survey_result)
        await self.session.commit()
        await self.session.refresh(survey_result)
        return survey_result