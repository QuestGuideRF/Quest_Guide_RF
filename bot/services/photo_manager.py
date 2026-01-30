import os
import hashlib
from pathlib import Path
from datetime import datetime
from aiogram import Bot
from aiogram.types import PhotoSize
from bot.config import Config
try:
    from PIL import Image, ImageEnhance, ImageFilter
    PIL_AVAILABLE = True
except ImportError:
    PIL_AVAILABLE = False
class PhotoManager:
    def __init__(self, config: Config):
        self.config = config
        self._ensure_directories()
    def _ensure_directories(self):
        Path(self.config.paths.users_photos_dir).mkdir(parents=True, exist_ok=True)
        Path(self.config.paths.reference_photos_dir).mkdir(parents=True, exist_ok=True)
        Path(self.config.paths.temp_dir).mkdir(parents=True, exist_ok=True)
    async def save_user_photo(
        self,
        bot: Bot,
        photo: PhotoSize,
        telegram_id: int,
        point_id: int,
    ) -> tuple[str, str]:
        user_dir = f"{self.config.paths.users_photos_dir}/{telegram_id}"
        Path(user_dir).mkdir(parents=True, exist_ok=True)
        file = await bot.get_file(photo.file_id)
        file_extension = file.file_path.split('.')[-1] if file.file_path else 'jpg'
        timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
        filename = f"point_{point_id}_{timestamp}.{file_extension}"
        full_path = f"{user_dir}/{filename}"
        await bot.download_file(file.file_path, full_path)
        file_hash = self._calculate_hash(full_path)
        relative_path = f"/uploads/users/{telegram_id}/{filename}"
        return relative_path, file_hash
    async def save_reference_photo(
        self,
        bot: Bot,
        photo: PhotoSize,
        point_id: int,
    ) -> str:
        point_dir = f"{self.config.paths.reference_photos_dir}/point_{point_id}"
        Path(point_dir).mkdir(parents=True, exist_ok=True)
        file = await bot.get_file(photo.file_id)
        file_extension = file.file_path.split('.')[-1] if file.file_path else 'jpg'
        timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
        filename = f"ref_{timestamp}.{file_extension}"
        full_path = f"{point_dir}/{filename}"
        await bot.download_file(file.file_path, full_path)
        return f"/uploads/reference/point_{point_id}/{filename}"
    def _calculate_hash(self, file_path: str) -> str:
        sha256_hash = hashlib.sha256()
        with open(file_path, "rb") as f:
            for byte_block in iter(lambda: f.read(4096), b""):
                sha256_hash.update(byte_block)
        return sha256_hash.hexdigest()
    def enhance_photo(self, file_path: str, brightness: float = 1.1, contrast: float = 1.15, sharpness: float = 1.2, saturation: float = 1.1) -> bool:
        if not PIL_AVAILABLE:
            return False
        try:
            full_path = self.get_full_path(file_path) if file_path.startswith('/') else file_path
            if not os.path.exists(full_path):
                return False
            img = Image.open(full_path)
            if img.mode == 'RGBA':
                img = img.convert('RGB')
            img = ImageEnhance.Brightness(img).enhance(brightness)
            img = ImageEnhance.Contrast(img).enhance(contrast)
            img = ImageEnhance.Sharpness(img).enhance(sharpness)
            img = ImageEnhance.Color(img).enhance(saturation)
            img.save(full_path, quality=95, optimize=True)
            return True
        except Exception:
            return False
    async def save_and_enhance_photo(self, bot: Bot, photo: PhotoSize, telegram_id: int, point_id: int, enhance: bool = True) -> tuple[str, str]:
        relative_path, file_hash = await self.save_user_photo(bot, photo, telegram_id, point_id)
        if enhance and PIL_AVAILABLE:
            self.enhance_photo(relative_path)
        return relative_path, file_hash
    def get_full_path(self, relative_path: str) -> str:
        return f"{self.config.paths.uploads_dir}{relative_path}"
    def cleanup_temp_files(self, max_age_hours: int = 24):
        import time
        temp_dir = Path(self.config.paths.temp_dir)
        if not temp_dir.exists():
            return
        current_time = time.time()
        max_age_seconds = max_age_hours * 3600
        deleted_count = 0
        for file_path in temp_dir.glob('*'):
            if file_path.is_file():
                file_age = current_time - file_path.stat().st_mtime
                if file_age > max_age_seconds:
                    file_path.unlink()
                    deleted_count += 1