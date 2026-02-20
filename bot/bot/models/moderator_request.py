from enum import Enum
from datetime import datetime
from sqlalchemy import ForeignKey, String, Text, DateTime, Enum as SQLEnum
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, TYPE_CHECKING
from bot.models.base import Base
if TYPE_CHECKING:
    from bot.models.user import User
class RequestStatus(str, Enum):
    PENDING = "pending"
    APPROVED = "approved"
    REJECTED = "rejected"
class ModeratorRequest(Base):
    __tablename__ = "moderator_requests"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        ForeignKey("users.id", ondelete="CASCADE"),
        index=True
    )
    message: Mapped[str] = mapped_column(Text)
    status: Mapped[RequestStatus] = mapped_column(
        SQLEnum(
            RequestStatus,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        ),
        default=RequestStatus.PENDING,
    )
    admin_comment: Mapped[Optional[str]] = mapped_column(Text, nullable=True)
    reviewed_by: Mapped[Optional[int]] = mapped_column(
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True
    )
    created_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow
    )
    reviewed_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    user: Mapped["User"] = relationship(
        "User",
        foreign_keys=[user_id],
        back_populates="moderator_requests"
    )
    reviewer: Mapped[Optional["User"]] = relationship(
        "User",
        foreign_keys=[reviewed_by]
    )