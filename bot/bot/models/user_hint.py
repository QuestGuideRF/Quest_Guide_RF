from sqlalchemy import Column, Integer, ForeignKey, DateTime, func
from sqlalchemy.orm import relationship
from bot.models.base import Base
class UserHint(Base):
    __tablename__ = "user_hints"
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    route_id = Column(Integer, ForeignKey("routes.id", ondelete="CASCADE"), nullable=False)
    point_id = Column(Integer, ForeignKey("points.id", ondelete="CASCADE"), nullable=False)
    hint_id = Column(Integer, ForeignKey("hints.id", ondelete="CASCADE"), nullable=False)
    used_at = Column(DateTime, server_default=func.now())
    user = relationship("User")
    route = relationship("Route")
    point = relationship("Point")
    hint = relationship("Hint", back_populates="user_hints")