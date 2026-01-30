import os
import hashlib
import logging
import re
from pathlib import Path
from typing import Optional
from datetime import datetime, timedelta
from sqlalchemy.ext.asyncio import AsyncSession
logger = logging.getLogger(__name__)
EMOJI_PATTERN = re.compile(
    "["
    "\U0001F600-\U0001F64F"
    "\U0001F300-\U0001F5FF"
    "\U0001F680-\U0001F6FF"
    "\U0001F1E0-\U0001F1FF"
    "\U00002702-\U000027B0"
    "\U000024C2-\U0001F251"
    "\U0001f926-\U0001f937"
    "\U00010000-\U0010ffff"
    "\u2640-\u2642"
    "\u2600-\u2B55"
    "\u200d"
    "\u23cf"
    "\u23e9"
    "\u231a"
    "\ufe0f"
    "\u3030"
    "]+",
    flags=re.UNICODE
)
def clean_text_for_tts(text: str) -> str:
    text = EMOJI_PATTERN.sub('', text)
    text = re.sub(r'-{3,}', ' ', text)
    text = re.sub(r'_{3,}', ' ', text)
    text = re.sub(r'={3,}', ' ', text)
    text = re.sub(r'\*{3,}', ' ', text)
    text = re.sub(r'[#@&\*\[\]\{\}<>\\|/~`^]', '', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()
class AudioGenerator:
    UPLOAD_DIR = Path("uploads/audio/points")
    CACHE_DURATION_DAYS = 30
    def __init__(self, session: AsyncSession):
        self.session = session
        self.upload_dir = Path(__file__).parent.parent.parent.parent / self.UPLOAD_DIR
        self.upload_dir.mkdir(parents=True, exist_ok=True)
    @staticmethod
    def get_text_hash(text: str) -> str:
        return hashlib.md5(text.encode('utf-8')).hexdigest()
    async def generate_audio(
        self,
        point_id: int,
        text: str,
        language: str = "ru",
        voice_id: int = 0
    ) -> Optional[str]:
        from bot.models.audio_cache import AudioCache
        from bot.repositories.base import BaseRepository
        try:
            text_with_voice = f"{text}|voice:{voice_id}"
            text_hash = self.get_text_hash(text_with_voice)
            repo = BaseRepository(AudioCache, self.session)
            from sqlalchemy import select
            result = await self.session.execute(
                select(AudioCache).where(
                    AudioCache.point_id == point_id,
                    AudioCache.language == language,
                    AudioCache.text_hash == text_hash,
                    (AudioCache.expires_at.is_(None) | (AudioCache.expires_at > datetime.now()))
                )
            )
            cached = result.scalars().first()
            if cached and os.path.exists(cached.audio_file_path):
                logger.info(f"Using cached audio for point {point_id}")
                return cached.audio_file_path
            filename = f"point_{point_id}_{language}_{text_hash[:8]}_v{voice_id}.mp3"
            filepath = self.upload_dir / filename
            clean_text = clean_text_for_tts(text)
            if not clean_text:
                logger.warning(f"Text is empty after cleaning for point {point_id}")
                return None
            success = await self._generate_with_edge_tts(clean_text, language, str(filepath), voice_id)
            if success:
                file_size = os.path.getsize(filepath)
                expires_at = datetime.now() + timedelta(days=self.CACHE_DURATION_DAYS)
                from sqlalchemy import select
                result = await self.session.execute(
                    select(AudioCache).where(
                        AudioCache.point_id == point_id,
                        AudioCache.language == language,
                        AudioCache.text_hash == text_hash
                    )
                )
                existing_cache = result.scalars().first()
                if existing_cache:
                    existing_cache.audio_file_path = str(filepath)
                    existing_cache.file_size = file_size
                    existing_cache.expires_at = expires_at
                else:
                    cache_entry = AudioCache(
                        point_id=point_id,
                        language=language,
                        text_hash=text_hash,
                        audio_file_path=str(filepath),
                        file_size=file_size,
                        expires_at=expires_at
                    )
                    self.session.add(cache_entry)
                relative_path = f"/uploads/audio/points/{filename}"
                field_name = f'audio_file_path_{language}'
                from bot.repositories.point import PointRepository
                point_repo = PointRepository(self.session)
                point = await point_repo.get(point_id)
                if point:
                    setattr(point, field_name, relative_path)
                await self.session.commit()
                logger.info(f"Generated audio for point {point_id}: {filepath}")
                return str(filepath)
            return None
        except Exception as e:
            logger.error(f"Error generating audio for point {point_id}: {e}")
            return None
    async def _generate_with_edge_tts(self, text: str, language: str, filepath: str, voice_id: int = 0) -> bool:
        try:
            import edge_tts
            lang_voices = {
                'ru': {'female': 'ru-RU-SvetlanaNeural', 'male': 'ru-RU-DmitryNeural'},
                'en': {'female': 'en-US-JennyNeural', 'male': 'en-US-GuyNeural'},
            }
            if language not in lang_voices:
                language = 'ru'
            voices = lang_voices[language]
            voice_type = 'female' if voice_id == 1 else 'male'
            voice = voices[voice_type]
            communicate = edge_tts.Communicate(text, voice)
            await communicate.save(filepath)
            if os.path.exists(filepath) and os.path.getsize(filepath) > 0:
                logger.info(f"Edge TTS ({voice}) generated: {filepath}")
                return True
            return False
        except ImportError:
            logger.error("edge-tts not installed")
            return False
        except Exception as e:
            logger.error(f"Edge TTS error: {e}")
            return False
    async def clear_expired_cache(self):
        from bot.models.audio_cache import AudioCache
        from sqlalchemy import select, delete
        try:
            result = await self.session.execute(
                select(AudioCache).where(
                    AudioCache.expires_at.isnot(None),
                    AudioCache.expires_at < datetime.now()
                )
            )
            expired = result.scalars().all()
            for cache in expired:
                if os.path.exists(cache.audio_file_path):
                    os.remove(cache.audio_file_path)
                    logger.info(f"Deleted expired audio: {cache.audio_file_path}")
            await self.session.execute(
                delete(AudioCache).where(
                    AudioCache.expires_at.isnot(None),
                    AudioCache.expires_at < datetime.now()
                )
            )
            await self.session.commit()
            logger.info(f"Cleared {len(expired)} expired audio files")
        except Exception as e:
            logger.error(f"Error clearing cache: {e}")
    async def get_audio_for_point(
        self,
        point_id: int,
        language: str = "ru",
        point_description: str = None,
        voice_id: int = 0
    ) -> Optional[str]:
        from bot.repositories.point import PointRepository
        point_repo = PointRepository(self.session)
        point = await point_repo.get(point_id)
        if not point:
            return None
        project_root = Path(__file__).parent.parent.parent.parent
        def get_absolute_path(relative_path: str) -> Optional[str]:
            if not relative_path:
                return None
            if os.path.isabs(relative_path) and os.path.exists(relative_path):
                return relative_path
            if relative_path.startswith('/'):
                abs_path = project_root / relative_path.lstrip('/')
            else:
                abs_path = project_root / relative_path
            return str(abs_path)
        if language == "ru" and hasattr(point, 'audio_file_path_ru') and point.audio_file_path_ru:
            abs_path = get_absolute_path(point.audio_file_path_ru)
            if abs_path and os.path.exists(abs_path):
                return abs_path
        if language == "en" and hasattr(point, 'audio_file_path_en') and point.audio_file_path_en:
            abs_path = get_absolute_path(point.audio_file_path_en)
            if abs_path and os.path.exists(abs_path):
                return abs_path
        if hasattr(point, 'audio_file_path') and point.audio_file_path and os.path.exists(point.audio_file_path):
            if language == "ru":
                return point.audio_file_path
        if not point.audio_enabled:
            return None
        if language == "ru":
            audio_text = (getattr(point, 'audio_text', None) or
                         point_description or
                         getattr(point, 'task_text', None) or
                         getattr(point, 'fact_text', None))
        elif language == "en":
            audio_text = (getattr(point, 'audio_text_en', None) or
                         getattr(point, 'task_text_en', None) or
                         getattr(point, 'fact_text_en', None))
            if not audio_text and point_description:
                audio_text = point_description
        else:
            audio_text = (point_description or
                         getattr(point, 'audio_text', None) or
                         getattr(point, 'task_text', None))
        if not audio_text:
            return None
        audio_path = await self.generate_audio(point_id, audio_text, language, voice_id)
        return audio_path