from datetime import datetime
from decimal import Decimal
from sqlalchemy import Integer, Text, DateTime, JSON, DECIMAL
from sqlalchemy.orm import Mapped, mapped_column
from bot.models.base import Base
class SurveyResult(Base):
    __tablename__ = "survey_results"
    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    user_id: Mapped[int] = mapped_column(Integer, nullable=False)
    progress_id: Mapped[int] = mapped_column(Integer, nullable=False, unique=True)
    route_id: Mapped[int] = mapped_column(Integer, nullable=False)
    answers: Mapped[dict] = mapped_column(JSON, nullable=False)
    reward_given: Mapped[Decimal] = mapped_column(DECIMAL(10, 2), default=0)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)