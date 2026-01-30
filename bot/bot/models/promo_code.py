from enum import Enum
from sqlalchemy import String, Integer, ForeignKey, Enum as SQLEnum, DECIMAL, DateTime, Boolean, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from datetime import datetime
from bot.models.base import Base, TimestampMixin
class DiscountType(str, Enum):
    PERCENTAGE = "percentage"
    FIXED = "fixed"
    FREE_ROUTE = "free_route"
class PromoCode(Base, TimestampMixin):
    __tablename__ = "promo_codes"
    id: Mapped[int] = mapped_column(primary_key=True)
    code: Mapped[str] = mapped_column(String(50), unique=True, index=True)
    description: Mapped[Optional[str]] = mapped_column(Text, nullable=True)
    discount_type: Mapped[DiscountType] = mapped_column(
        SQLEnum(DiscountType, native_enum=False, values_callable=lambda x: [e.value for e in x]),
        default=DiscountType.PERCENTAGE,
    )
    discount_value: Mapped[Optional[float]] = mapped_column(DECIMAL(10, 2), nullable=True)
    route_id: Mapped[Optional[int]] = mapped_column(ForeignKey("routes.id", ondelete="SET NULL"), nullable=True)
    max_uses: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    used_count: Mapped[int] = mapped_column(Integer, default=0)
    valid_from: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    valid_until: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    is_active: Mapped[bool] = mapped_column(Boolean, default=True)
    created_by: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    route: Mapped[Optional["Route"]] = relationship("Route", back_populates="promo_codes")