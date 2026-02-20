from decimal import Decimal
from datetime import datetime
from sqlalchemy import Integer, String, ForeignKey, Numeric, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from bot.models.base import Base
class ReferralReward(Base):
    __tablename__ = "referral_rewards"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"))
    referral_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"))
    level: Mapped[int] = mapped_column(Integer)
    reward_type: Mapped[str] = mapped_column(String(50))
    reward_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(10, 2), nullable=True)
    promo_code_id: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    route_id: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
    user: Mapped["User"] = relationship(
        "User",
        back_populates="referral_rewards",
        foreign_keys=[user_id]
    )
    referral: Mapped["User"] = relationship(
        "User",
        foreign_keys=[referral_id]
    )