from sqlalchemy import String, Boolean, ForeignKey
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import List, Optional
from bot.models.base import Base, TimestampMixin
class City(Base, TimestampMixin):
    __tablename__ = "cities"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(255), unique=True, index=True)
    name_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True, comment="Название на английском")
    description: Mapped[Optional[str]] = mapped_column(String(1000), nullable=True)
    description_en: Mapped[Optional[str]] = mapped_column(String(1000), nullable=True, comment="Описание на английском")
    is_active: Mapped[bool] = mapped_column(Boolean, default=True)
    creator_id: Mapped[Optional[int]] = mapped_column(
        ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True,
        index=True,
        comment="ID модератора-создателя"
    )
    routes: Mapped[List["Route"]] = relationship(
        "Route",
        back_populates="city",
        cascade="all, delete-orphan",
    )