from sqlalchemy import Integer, ForeignKey, DECIMAL, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from datetime import datetime
from bot.models.base import Base
class PromoCodeUse(Base):
    __tablename__ = "promo_code_uses"
    id: Mapped[int] = mapped_column(primary_key=True)
    promo_code_id: Mapped[int] = mapped_column(ForeignKey("promo_codes.id", ondelete="CASCADE"))
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"))
    route_id: Mapped[Optional[int]] = mapped_column(ForeignKey("routes.id", ondelete="SET NULL"), nullable=True)
    discount_amount: Mapped[Optional[float]] = mapped_column(DECIMAL(10, 2), nullable=True)
    used_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
    promo_code: Mapped["PromoCode"] = relationship("PromoCode")
    user: Mapped["User"] = relationship("User")