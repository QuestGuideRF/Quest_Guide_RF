from sqlalchemy import Column, Integer, Text, Boolean, String, ForeignKey, DateTime, func
from sqlalchemy.orm import relationship
from bot.models.base import Base
class Hint(Base):
    __tablename__ = "hints"
    id = Column(Integer, primary_key=True, autoincrement=True)
    point_id = Column(Integer, ForeignKey("points.id", ondelete="CASCADE"), nullable=False)
    level = Column(Integer, nullable=False, comment="1=легкая, 2=средняя, 3=детальная")
    text = Column(Text, nullable=False, comment="Текст подсказки")
    text_en = Column(Text, nullable=True, comment="Текст подсказки на английском")
    has_map = Column(Boolean, default=False, nullable=False, comment="Есть ли мини-карта")
    map_image_path = Column(String(500), nullable=True, comment="Путь к изображению карты")
    image_path = Column(String(500), nullable=True, comment="Путь к фото подсказки")
    order = Column(Integer, default=0, nullable=False, comment="Порядок показа")
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    point = relationship("Point", back_populates="hints")
    user_hints = relationship("UserHint", back_populates="hint", cascade="all, delete-orphan")
    @property
    def level_name(self) -> str:
        levels = {1: "легкая", 2: "средняя", 3: "детальная"}
        return levels.get(self.level, "неизвестная")
    @property
    def level_emoji(self) -> str:
        emojis = {1: "💡", 2: "🔦", 3: "🎯"}
        return emojis.get(self.level, "💡")