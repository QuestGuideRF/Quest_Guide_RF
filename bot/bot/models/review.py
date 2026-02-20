<<<<<<< HEAD
from decimal import Decimal
from sqlalchemy import Integer, ForeignKey, Text, Column, Boolean, Numeric
=======
from sqlalchemy import Integer, ForeignKey, Text, Column
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from bot.models.base import Base, TimestampMixin
class Review(Base, TimestampMixin):
    __tablename__ = "reviews"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"))
    route_id: Mapped[int] = mapped_column(ForeignKey("routes.id", ondelete="CASCADE"))
    progress_id: Mapped[int] = mapped_column(ForeignKey("user_progress.id", ondelete="CASCADE"), unique=True)
    rating: Mapped[int] = mapped_column(Integer, comment="Рейтинг 1-5")
<<<<<<< HEAD
    text: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Текст отзыва")
    reward_given: Mapped[bool] = mapped_column(Boolean, default=False)
    reward_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(10, 2), nullable=True)
=======
    text: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Текст отзыва")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
