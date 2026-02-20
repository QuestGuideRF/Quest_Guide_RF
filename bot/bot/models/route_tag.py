from sqlalchemy import Integer, ForeignKey, DateTime
from sqlalchemy.orm import Mapped, mapped_column, relationship
from datetime import datetime
from bot.models.base import Base
class RouteTag(Base):
    __tablename__ = "route_tags"
    id: Mapped[int] = mapped_column(primary_key=True)
    route_id: Mapped[int] = mapped_column(ForeignKey("routes.id", ondelete="CASCADE"))
    tag_id: Mapped[int] = mapped_column(ForeignKey("tags.id", ondelete="CASCADE"))
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow, server_default="CURRENT_TIMESTAMP")
    route: Mapped["Route"] = relationship("Route", back_populates="route_tags")
    tag: Mapped["Tag"] = relationship("Tag", back_populates="route_tags")