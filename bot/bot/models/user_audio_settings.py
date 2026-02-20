from sqlalchemy import String, Integer, ForeignKey, Boolean
from sqlalchemy.orm import Mapped, mapped_column, relationship
from typing import TYPE_CHECKING, Optional
from bot.models.base import Base, TimestampMixin
if TYPE_CHECKING:
    from bot.models.user import User
class UserAudioSettings(Base, TimestampMixin):
    __tablename__ = "user_audio_settings"
    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), unique=True)
    auto_play: Mapped[bool] = mapped_column(Boolean, default=False, comment="Автовоспроизведение аудио")
    language: Mapped[str] = mapped_column(String(5), default="ru", comment="Язык аудио")
    voice_id: Mapped[Optional[int]] = mapped_column(Integer, nullable=True, default=0, comment="ID голоса (0=мужской, 1=женский)")
    speech_rate: Mapped[Optional[int]] = mapped_column(Integer, nullable=True, default=150, comment="Скорость речи (слов в минуту)")
    user: Mapped["User"] = relationship("User", back_populates="audio_settings")