from enum import Enum
from datetime import datetime
from decimal import Decimal
from sqlalchemy import BigInteger, String, Enum as SQLEnum, Boolean, DateTime, ForeignKey, Integer, Numeric
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import List, Optional, TYPE_CHECKING
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.user_audio_settings import UserAudioSettings
    from bot.models.token_balance import TokenBalance
    from bot.models.token_transaction import TokenTransaction
    from bot.models.token_deposit import TokenDeposit
    from bot.models.user_search_limit import UserSearchLimit
    from bot.models.moderator_request import ModeratorRequest
    from bot.models.moderator_balance import ModeratorBalance
    from bot.models.referral_reward import ReferralReward
class UserRole(str, Enum):
    USER = "user"
    MODERATOR = "moderator"
    ADMIN = "admin"
class User(Base, TimestampMixin):
    __tablename__ = "users"
    id: Mapped[int] = mapped_column(BigInteger, primary_key=True)
    telegram_id: Mapped[int] = mapped_column(BigInteger, unique=True, index=True)
    username: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    first_name: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    last_name: Mapped[Optional[str]] = mapped_column(String(255), nullable=True)
    role: Mapped[UserRole] = mapped_column(
        SQLEnum(UserRole, native_enum=False),
        default=UserRole.USER,
        server_default=UserRole.USER.value,
    )
    is_banned: Mapped[bool] = mapped_column(Boolean, default=False, server_default="0")
    ban_until: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    ban_reason: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    banned_by: Mapped[Optional[int]] = mapped_column(BigInteger, nullable=True)
    banned_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    language: Mapped[str] = mapped_column(String(5), default="ru", server_default="ru")
    show_map: Mapped[bool] = mapped_column(Boolean, default=False, server_default="0", comment="Показывать кнопку Яндекс.Карты в блоке «Как добраться»")
    referred_by_id: Mapped[Optional[int]] = mapped_column(
        BigInteger,
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True,
    )
    referral_level: Mapped[int] = mapped_column(Integer, default=0)
    paid_referrals_count: Mapped[int] = mapped_column(Integer, default=0)
    referral_earnings: Mapped[Decimal] = mapped_column(Numeric(10, 2), default=Decimal("0.00"))
    is_partner: Mapped[bool] = mapped_column(Boolean, default=False)
    progresses: Mapped[List["UserProgress"]] = relationship(
        "UserProgress",
        back_populates="user",
        cascade="all, delete-orphan",
    )
    payments: Mapped[List["Payment"]] = relationship(
        "Payment",
        back_populates="user",
        cascade="all, delete-orphan",
    )
    audio_settings: Mapped[Optional["UserAudioSettings"]] = relationship(
        "UserAudioSettings",
        back_populates="user",
        uselist=False,
        cascade="all, delete-orphan",
    )
    token_balance: Mapped[Optional["TokenBalance"]] = relationship(
        "TokenBalance",
        back_populates="user",
        uselist=False,
        cascade="all, delete-orphan",
    )
    token_transactions: Mapped[List["TokenTransaction"]] = relationship(
        "TokenTransaction",
        back_populates="user",
        foreign_keys="TokenTransaction.user_id",
        cascade="all, delete-orphan",
    )
    token_deposits: Mapped[List["TokenDeposit"]] = relationship(
        "TokenDeposit",
        back_populates="user",
        cascade="all, delete-orphan",
    )
    search_limit: Mapped[Optional["UserSearchLimit"]] = relationship(
        "UserSearchLimit",
        back_populates="user",
        uselist=False,
        cascade="all, delete-orphan",
    )
    moderator_requests: Mapped[List["ModeratorRequest"]] = relationship(
        "ModeratorRequest",
        back_populates="user",
        foreign_keys="ModeratorRequest.user_id",
        cascade="all, delete-orphan",
    )
    moderator_balance: Mapped[Optional["ModeratorBalance"]] = relationship(
        "ModeratorBalance",
        back_populates="user",
        uselist=False,
        cascade="all, delete-orphan",
    )
    referral_rewards: Mapped[List["ReferralReward"]] = relationship(
        "ReferralReward",
        back_populates="user",
        foreign_keys="ReferralReward.user_id",
        cascade="all, delete-orphan",
    )