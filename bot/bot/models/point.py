from sqlalchemy import String, Integer, ForeignKey, Text, Boolean, Float
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import List, Optional, TYPE_CHECKING
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.audio_cache import AudioCache
    from bot.models.task import Task
class Point(Base, TimestampMixin):
    __tablename__ = "points"
    id: Mapped[int] = mapped_column(primary_key=True)
    route_id: Mapped[int] = mapped_column(ForeignKey("routes.id", ondelete="CASCADE"))
    order: Mapped[int] = mapped_column(Integer, index=True)
    name: Mapped[str] = mapped_column(String(255))
    name_en: Mapped[Optional[str]] = mapped_column(String(255), nullable=True, comment="Название на английском")
    address: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    fact_text: Mapped[Optional[str]] = mapped_column(Text, nullable=True)
    fact_text_en: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Факт на английском")
    require_pose: Mapped[Optional[str]] = mapped_column(String(100), nullable=True)
    min_people: Mapped[int] = mapped_column(Integer, default=1)
    latitude: Mapped[Optional[float]] = mapped_column(Float, nullable=True)
    longitude: Mapped[Optional[float]] = mapped_column(Float, nullable=True)
    is_free: Mapped[bool] = mapped_column(Boolean, default=False)
    audio_enabled: Mapped[bool] = mapped_column(Boolean, default=False)
    audio_file_path: Mapped[Optional[str]] = mapped_column(String(500), nullable=True)
    audio_file_path_ru: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Путь к аудиофайлу (русский)")
    audio_file_path_en: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Путь к аудиофайлу (английский)")
    audio_language: Mapped[str] = mapped_column(String(5), default="ru")
    audio_text: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Текст для озвучки аудиогида")
    audio_text_en: Mapped[Optional[str]] = mapped_column(Text, nullable=True, comment="Текст для озвучки на английском")
    task_type: Mapped[Optional[str]] = mapped_column(String(20), nullable=True, comment="photo, text, riddle (deprecated, use tasks)")
    text_answer: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Правильный ответ (deprecated, use tasks)")
    text_answer_hint: Mapped[Optional[str]] = mapped_column(String(500), nullable=True, comment="Подсказка к ответу (deprecated, use tasks)")
    accept_partial_match: Mapped[Optional[bool]] = mapped_column(Boolean, nullable=True, comment="Частичное совпадение (deprecated, use tasks)")
    max_attempts: Mapped[Optional[int]] = mapped_column(Integer, nullable=True, comment="Максимум попыток (deprecated, use tasks)")
    route: Mapped["Route"] = relationship("Route", back_populates="points")
    reference_images: Mapped[List["ReferenceImage"]] = relationship(
        "ReferenceImage",
        back_populates="point",
        cascade="all, delete-orphan",
    )
    hints: Mapped[List["Hint"]] = relationship(
        "Hint",
        back_populates="point",
        cascade="all, delete-orphan",
        order_by="Hint.order",
    )
    audio_caches: Mapped[List["AudioCache"]] = relationship(
        "AudioCache",
        back_populates="point",
        cascade="all, delete-orphan",
    )
    tasks: Mapped[List["Task"]] = relationship(
        "Task",
        back_populates="point",
        cascade="all, delete-orphan",
        order_by="Task.order",
    )