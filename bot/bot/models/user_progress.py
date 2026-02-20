from enum import Enum
from datetime import datetime
from sqlalchemy import BigInteger, Integer, ForeignKey, Boolean, DateTime, Enum as SQLEnum, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from bot.models.base import Base, TimestampMixin
class ProgressStatus(str, Enum):
    IN_PROGRESS = "in_progress"
    COMPLETED = "completed"
    ABANDONED = "abandoned"
    PAUSED = "paused"
class UserProgress(Base, TimestampMixin):
    __tablename__ = "user_progress"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"))
    route_id: Mapped[int] = mapped_column(ForeignKey("routes.id", ondelete="CASCADE"))
    current_point_id: Mapped[Optional[int]] = mapped_column(
        ForeignKey("points.id", ondelete="SET NULL"),
        nullable=True,
    )
    current_point_order: Mapped[int] = mapped_column(Integer, default=0)
    status: Mapped[ProgressStatus] = mapped_column(
        SQLEnum(ProgressStatus, native_enum=False),
        default=ProgressStatus.IN_PROGRESS,
    )
    started_at: Mapped[datetime] = mapped_column(DateTime(timezone=True))
    completed_at: Mapped[Optional[datetime]] = mapped_column(DateTime(timezone=True), nullable=True)
    paused_at: Mapped[Optional[datetime]] = mapped_column(DateTime(timezone=True), nullable=True)
    total_paused_seconds: Mapped[int] = mapped_column(Integer, default=0)
    is_paused: Mapped[bool] = mapped_column(Boolean, default=False)
    points_completed: Mapped[int] = mapped_column(Integer, default=0)
    photo_hashes: Mapped[Optional[str]] = mapped_column(Text, nullable=True)
    user: Mapped["User"] = relationship("User", back_populates="progresses")
    route: Mapped["Route"] = relationship("Route", back_populates="progresses")