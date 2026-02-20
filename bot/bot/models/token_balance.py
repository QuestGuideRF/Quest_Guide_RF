from decimal import Decimal
from sqlalchemy import ForeignKey, DECIMAL, BigInteger
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import TYPE_CHECKING
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.user import User
class TokenBalance(Base, TimestampMixin):
    __tablename__ = "token_balances"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="CASCADE"),
        unique=True
    )
    balance: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_deposited: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_spent: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_transferred_out: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    total_transferred_in: Mapped[Decimal] = mapped_column(
        DECIMAL(15, 2),
        default=Decimal("0.00")
    )
    user: Mapped["User"] = relationship("User", back_populates="token_balance")