from sqlalchemy import String, Integer, Enum, DateTime, func
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional, List
from datetime import datetime
from bot.models.base import Base
class TagType:
    TOPIC = "topic"
    AGE = "age"
    DIFFICULTY = "difficulty"
    DURATION = "duration"
    SEASON = "season"
class Tag(Base):
    __tablename__ = "tags"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))
    slug: Mapped[str] = mapped_column(String(100), unique=True)
    type: Mapped[str] = mapped_column(Enum("topic", "age", "difficulty", "duration", "season", name="tag_type"))
    icon: Mapped[Optional[str]] = mapped_column(String(50), nullable=True)
    color: Mapped[Optional[str]] = mapped_column(String(7), nullable=True)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
    )
    route_tags: Mapped[List["RouteTag"]] = relationship("RouteTag", back_populates="tag", cascade="all, delete-orphan")