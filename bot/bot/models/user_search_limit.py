from datetime import datetime
from sqlalchemy import ForeignKey, Integer, DateTime, BigInteger
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, TYPE_CHECKING
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.user import User
class UserSearchLimit(Base, TimestampMixin):
    __tablename__ = "user_search_limits"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="CASCADE"),
        unique=True
    )
    search_count: Mapped[int] = mapped_column(Integer, default=0)
    first_search_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    blocked_until: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    MAX_SEARCHES = 5
    BLOCK_DURATION_MINUTES = 30
    WINDOW_DURATION_MINUTES = 30
    user: Mapped["User"] = relationship("User", back_populates="search_limit")
    def is_blocked(self) -> bool:
        if self.blocked_until is None:
            return False
        return datetime.utcnow() < self.blocked_until
    def can_search(self) -> bool:
        if self.is_blocked():
            return False
        return self.search_count < self.MAX_SEARCHES
    def get_remaining_block_time(self) -> int:
        if self.blocked_until is None:
            return 0
        remaining = (self.blocked_until - datetime.utcnow()).total_seconds()
        return max(0, int(remaining))