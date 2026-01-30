from sqlalchemy import String, Integer, ForeignKey, Text, Boolean
from sqlalchemy.dialects.mysql import INTEGER
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import List, Optional, TYPE_CHECKING
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.point import Point
class Task(Base, TimestampMixin):
    __tablename__ = "tasks"
    id: Mapped[int] = mapped_column(primary_key=True)
    point_id: Mapped[int] = mapped_column(INTEGER(unsigned=True), ForeignKey("points.id", ondelete="CASCADE"))
    order: Mapped[int] = mapped_column(Integer, default=0, comment="Порядок задания в точке")
    task_text: Mapped[str] = mapped_column(Text, comment="Текст задания")
    task_text_en: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Текст задания на английском")
    task_type: Mapped[str] = mapped_column(String(20), default="photo", comment="photo, text, riddle")
    text_answer: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Правильный ответ")
    text_answer_hint: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Подсказка к ответу")
    accept_partial_match: Mapped[bool] = mapped_column(Boolean, default=False, comment="Частичное совпадение")
    max_attempts: Mapped[int] = mapped_column(Integer, default=3, comment="Максимум попыток")
    point: Mapped["Point"] = relationship("Point", back_populates="tasks")