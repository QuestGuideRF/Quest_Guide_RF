from enum import Enum
from decimal import Decimal
from datetime import datetime
from sqlalchemy import ForeignKey, String, DECIMAL, BigInteger, Enum as SQLEnum, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, TYPE_CHECKING
from bot.models.base import Base
from bot.models.token_transaction import PaymentMethod
if TYPE_CHECKING:
    from bot.models.user import User
class DepositStatus(str, Enum):
    PENDING = "pending"
    COMPLETED = "completed"
    FAILED = "failed"
    CANCELLED = "cancelled"
class TokenDeposit(Base):
    __tablename__ = "token_deposits"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="CASCADE"),
        index=True
    )
    amount: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    payment_amount: Mapped[Decimal] = mapped_column(DECIMAL(15, 2))
    payment_method: Mapped[PaymentMethod] = mapped_column(
        SQLEnum(PaymentMethod, native_enum=False)
    )
    payment_id: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    status: Mapped[DepositStatus] = mapped_column(
        SQLEnum(DepositStatus, native_enum=False),
        default=DepositStatus.PENDING
    )
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
    completed_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    user: Mapped["User"] = relationship("User", back_populates="token_deposits")