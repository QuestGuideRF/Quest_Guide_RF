from datetime import datetime
from sqlalchemy import String, Text, Integer, Boolean, BigInteger, ForeignKey, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import List
from bot.models.base import Base, TimestampMixin
class Achievement(Base, TimestampMixin):
    __tablename__ = "achievements"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(255))
    description: Mapped[str] = mapped_column(Text)
    icon: Mapped[str] = mapped_column(String(10), default="🏆")
    category: Mapped[str] = mapped_column(String(100), default="Общие")
    order: Mapped[int] = mapped_column(Integer, default=0)
    is_hidden: Mapped[bool] = mapped_column(Boolean, default=False)
    condition_type: Mapped[str] = mapped_column(String(50))
    condition_value: Mapped[int | None] = mapped_column(Integer, nullable=True)
    user_achievements: Mapped[List["UserAchievement"]] = relationship(
        "UserAchievement",
        back_populates="achievement",
        cascade="all, delete-orphan",
    )
class UserAchievement(Base):
    __tablename__ = "user_achievements"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(BigInteger, ForeignKey("users.telegram_id", ondelete="CASCADE"))
    achievement_id: Mapped[int] = mapped_column(ForeignKey("achievements.id", ondelete="CASCADE"))
    earned_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)