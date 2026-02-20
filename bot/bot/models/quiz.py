from datetime import datetime
from typing import Optional
from decimal import Decimal
from sqlalchemy import Integer, String, Text, DateTime, Enum, ForeignKey, DECIMAL
from sqlalchemy.orm import Mapped, mapped_column
from bot.models.base import Base
class QuizQuestion(Base):
    __tablename__ = "quiz_questions"
    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    route_id: Mapped[int] = mapped_column(Integer, nullable=False)
    question: Mapped[str] = mapped_column(Text, nullable=False)
    question_en: Mapped[Optional[str]] = mapped_column(Text, nullable=True)
    option_a: Mapped[str] = mapped_column(String(255), nullable=False)
    option_a_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    option_b: Mapped[str] = mapped_column(String(255), nullable=False)
    option_b_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    option_c: Mapped[str] = mapped_column(String(255), nullable=False)
    option_c_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    option_d: Mapped[str] = mapped_column(String(255), nullable=False)
    option_d_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    correct_option: Mapped[str] = mapped_column(String(1), nullable=False)
    reward_amount: Mapped[Decimal] = mapped_column(DECIMAL(10, 2), default=0)
    order: Mapped[int] = mapped_column(Integer, default=0)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
class QuizResult(Base):
    __tablename__ = "quiz_results"
    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    user_id: Mapped[int] = mapped_column(Integer, nullable=False)
    progress_id: Mapped[int] = mapped_column(Integer, nullable=False, unique=True)
    route_id: Mapped[int] = mapped_column(Integer, nullable=False)
    correct_count: Mapped[int] = mapped_column(Integer, default=0)
    total_count: Mapped[int] = mapped_column(Integer, default=0)
    reward_given: Mapped[Decimal] = mapped_column(DECIMAL(10, 2), default=0)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)