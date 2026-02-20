from enum import Enum
from decimal import Decimal
from datetime import datetime
from sqlalchemy import ForeignKey, String, DECIMAL, BigInteger, Enum as SQLEnum, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, TYPE_CHECKING
from bot.models.base import Base
if TYPE_CHECKING:
    from bot.models.user import User
    from bot.models.route import Route
class TransactionType(str, Enum):
    DEPOSIT = "deposit"
    PURCHASE = "purchase"
    TRANSFER_OUT = "transfer_out"
    TRANSFER_IN = "transfer_in"
    REFUND = "refund"
<<<<<<< HEAD
    ADJUSTMENT = "adjustment"
    REFERRAL_REWARD = "referral_reward"
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
class PaymentMethod(str, Enum):
    YOOKASSA = "yookassa"
    TELEGRAM_STARS = "telegram_stars"
    TRANSFER = "transfer"
    SYSTEM = "system"
class TransactionStatus(str, Enum):
    PENDING = "pending"
    COMPLETED = "completed"
    FAILED = "failed"
    CANCELLED = "cancelled"
class TokenTransaction(Base):
    __tablename__ = "token_transactions"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="CASCADE"),
        index=True
    )
    type: Mapped[TransactionType] = mapped_column(
        SQLEnum(
            TransactionType,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        )
    )
    amount: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    balance_before: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    balance_after: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    description: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    related_user_id: Mapped[Optional[int]] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True
    )
    related_route_id: Mapped[Optional[int]] = mapped_column(
        ForeignKey("routes.id", ondelete="SET NULL"),
        nullable=True
    )
    payment_method: Mapped[Optional[PaymentMethod]] = mapped_column(
        SQLEnum(
            PaymentMethod,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        ),
        nullable=True,
    )
    external_payment_id: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    status: Mapped[TransactionStatus] = mapped_column(
        SQLEnum(
            TransactionStatus,
            native_enum=False,
            values_callable=lambda x: [e.value for e in x],
        ),
        default=TransactionStatus.COMPLETED,
    )
    created_at: Mapped[datetime] = mapped_column(
        DateTime,
        default=datetime.utcnow
    )
    user: Mapped["User"] = relationship(
        "User",
        foreign_keys=[user_id],
        back_populates="token_transactions"
    )
    related_user: Mapped[Optional["User"]] = relationship(
        "User",
        foreign_keys=[related_user_id]
    )
    related_route: Mapped[Optional["Route"]] = relationship("Route")