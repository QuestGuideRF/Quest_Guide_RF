from enum import Enum
from decimal import Decimal
from datetime import datetime
from sqlalchemy import ForeignKey, String, DECIMAL, BigInteger, Enum as SQLEnum, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, List, TYPE_CHECKING
from bot.models.base import Base
if TYPE_CHECKING:
    from bot.models.user import User
    from bot.models.route import Route
class ModeratorTransactionType(str, Enum):
    EARNING = "earning"
    WITHDRAWAL = "withdrawal"
    ADJUSTMENT = "adjustment"
class WithdrawalStatus(str, Enum):
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    REJECTED = "rejected"
class ModeratorBalance(Base):
    __tablename__ = "moderator_balances"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        ForeignKey("users.id", ondelete="CASCADE"),
        unique=True,
        index=True
    )
    balance: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_earned: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_withdrawn: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    created_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow
    )
    updated_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow,
        onupdate=datetime.utcnow
    )
    user: Mapped["User"] = relationship("User", back_populates="moderator_balance")
class ModeratorTransaction(Base):
    __tablename__ = "moderator_transactions"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        ForeignKey("users.id", ondelete="CASCADE"),
        index=True
    )
    type: Mapped[ModeratorTransactionType] = mapped_column(
        SQLEnum(
            ModeratorTransactionType,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        )
    )
    amount: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    route_id: Mapped[Optional[int]] = mapped_column(
        ForeignKey("routes.id", ondelete="SET NULL"),
        nullable=True
    )
    buyer_user_id: Mapped[Optional[int]] = mapped_column(
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True
    )
    description: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    created_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow
    )
    user: Mapped["User"] = relationship(
        "User",
        foreign_keys=[user_id]
    )
    route: Mapped[Optional["Route"]] = relationship("Route")
    buyer: Mapped[Optional["User"]] = relationship(
        "User",
        foreign_keys=[buyer_user_id]
    )
class WithdrawalRequest(Base):
    __tablename__ = "withdrawal_requests"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        ForeignKey("users.id", ondelete="CASCADE"),
        index=True
    )
    amount: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    payment_details: Mapped[str] = mapped_column(String(1000))
    status: Mapped[WithdrawalStatus] = mapped_column(
        SQLEnum(
            WithdrawalStatus,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        ),
        default=WithdrawalStatus.PENDING,
    )
    admin_comment: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    processed_by: Mapped[Optional[int]] = mapped_column(
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True
    )
    created_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow
    )
    processed_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    user: Mapped["User"] = relationship(
        "User",
        foreign_keys=[user_id]
    )
    processor: Mapped[Optional["User"]] = relationship(
        "User",
        foreign_keys=[processed_by]
    )