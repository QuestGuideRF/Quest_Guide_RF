from sqlalchemy import String, Integer, ForeignKey, DateTime, TIMESTAMP
from sqlalchemy.orm import Mapped, mapped_column, relationship
from sqlalchemy.sql import func
from typing import Optional, TYPE_CHECKING
from datetime import datetime
from bot.models.base import Base
if TYPE_CHECKING:
    from bot.models.point import Point
class AudioCache(Base):
    __tablename__ = "audio_cache"
    id: Mapped[int] = mapped_column(primary_key=True)
    point_id: Mapped[int] = mapped_column(ForeignKey("points.id", ondelete="CASCADE"))
    language: Mapped[str] = mapped_column(String(5), default="ru")
    text_hash: Mapped[str] = mapped_column(String(64))
    audio_file_path: Mapped[str] = mapped_column(String(500))
    file_size: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    duration_seconds: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)
    created_at: Mapped[datetime] = mapped_column(TIMESTAMP, server_default=func.now())
    expires_at: Mapped[Optional[datetime]] = mapped_column(DateTime, nullable=True)
    point: Mapped["Point"] = relationship("Point", back_populates="audio_caches")