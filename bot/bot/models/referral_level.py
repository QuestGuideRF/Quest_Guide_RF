from enum import Enum
from decimal import Decimal
from datetime import datetime
from sqlalchemy import Integer, String, Text, Numeric, Boolean
from sqlalchemy.orm import Mapped, mapped_column
from bot.models.base import Base, TimestampMixin
class RewardType(str, Enum):
    TOKENS_PER_REFERRAL = "tokens_per_referral"
    DISCOUNT_CODE = "discount_code"
    FREE_ROUTE = "free_route"
    SPECIAL = "special"
class ReferralLevel(Base, TimestampMixin):
    __tablename__ = "referral_levels"
    id: Mapped[int] = mapped_column(primary_key=True)
    level: Mapped[int] = mapped_column(Integer, unique=True)
    name: Mapped[str] = mapped_column(String(100))
    name_en: Mapped[str] = mapped_column(String(100), nullable=True)
    description: Mapped[str] = mapped_column(Text, nullable=True)
    description_en: Mapped[str] = mapped_column(Text, nullable=True)
    required_referrals: Mapped[int] = mapped_column(Integer)
    reward_type: Mapped[str] = mapped_column(String(50))
    reward_value: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=True)
    icon: Mapped[str] = mapped_column(String(10), default="🎁")
    is_active: Mapped[bool] = mapped_column(Boolean, default=True)