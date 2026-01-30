from sqlalchemy import String, ForeignKey, LargeBinary
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import Optional
from bot.models.base import Base, TimestampMixin
class ReferenceImage(Base, TimestampMixin):
    __tablename__ = "reference_images"
    id: Mapped[int] = mapped_column(primary_key=True)
    point_id: Mapped[int] = mapped_column(ForeignKey("points.id", ondelete="CASCADE"))
    file_id: Mapped[str] = mapped_column(String(255))
    file_path: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    embedding: Mapped[Optional[bytes]] = mapped_column(LargeBinary, nullable=True)
    point: Mapped["Point"] = relationship("Point", back_populates="reference_images")