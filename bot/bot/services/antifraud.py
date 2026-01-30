from typing import List, Tuple, Dict
import hashlib
from datetime import datetime, timedelta
from typing import Optional
from PIL import Image
from PIL.ExifTags import TAGS
from bot.services.antifraud_fix import simple_cache
class AntiFraudService:
    def __init__(self):
        self.redis = simple_cache
    async def check_photo_hash(
        self,
        photo_path: str,
        user_id: int,
        route_id: int,
    ) -> Tuple[bool, str]:
        photo_hash = self._calculate_photo_hash(photo_path)
        key = f"photo_hash:{user_id}:{route_id}:{photo_hash}"
        exists = await self.redis.exists(key)
        if exists:
            return False, "Это фото уже использовалось в данном маршруте"
        await self.redis.setex(key, 86400, "1")
        return True, "Фото уникально"
    def _calculate_photo_hash(self, photo_path: str) -> str:
        with open(photo_path, "rb") as f:
            return hashlib.sha256(f.read()).hexdigest()
    async def check_exif_date(
        self,
        photo_path: str,
        max_age_hours: int = 24,
    ) -> Tuple[bool, str]:
        try:
            image = Image.open(photo_path)
            exif_data = image._getexif()
            if not exif_data or len(exif_data) < 5:
                return False, "❌ Фото не содержит данных камеры. Сделайте новое фото на телефон."
            date_taken = None
            for tag_id, value in exif_data.items():
                tag_name = TAGS.get(tag_id, tag_id)
                if tag_name in ("DateTime", "DateTimeOriginal", "DateTimeDigitized"):
                    try:
                        date_taken = datetime.strptime(value, "%Y:%m:%d %H:%M:%S")
                    except:
                        pass
                    break
            if not date_taken:
                return False, "❌ Фото не содержит даты съемки. Сделайте новое фото на телефон."
            age = datetime.now() - date_taken
            if age > timedelta(hours=max_age_hours):
                return False, f"❌ Фото слишком старое ({age.days} дн.). Сделайте новое фото на месте."
            return True, f"✅ Фото свежее ({int(age.total_seconds() / 3600)} ч.), EXIF OK"
        except Exception as e:
            return False, "❌ Ошибка чтения фото. Убедитесь, что это реальная фотография."
    async def check_timing(
        self,
        user_id: int,
        route_id: int,
        point_order: int,
        min_seconds_per_point: int = 15,
    ) -> Tuple[bool, str]:
        key = f"timing:{user_id}:{route_id}:{point_order}"
        last_time_str = await self.redis.get(key)
        current_time = datetime.now()
        if last_time_str:
            last_time = datetime.fromisoformat(last_time_str)
            elapsed = (current_time - last_time).total_seconds()
            if elapsed < min_seconds_per_point:
                return False, f"Слишком быстро! Подождите еще {int(min_seconds_per_point - elapsed)} секунд"
        await self.redis.setex(
            key,
            3600,
            current_time.isoformat(),
        )
        return True, "Timing в норме"
    async def check_global_rate_limit(
        self,
        user_id: int,
        max_photos_per_hour: int = 20,
    ) -> Tuple[bool, str]:
        key = f"rate_limit:photos:{user_id}"
        count = await self.redis.incr(key)
        if count == 1:
            await self.redis.expire(key, 3600)
        if count > max_photos_per_hour:
            return False, "Превышен лимит отправки фото. Подождите немного."
        return True, "Rate limit OK"
    async def perform_all_checks(
        self,
        photo_path: str,
        user_id: int,
        route_id: int,
        point_order: int,
    ) -> Tuple[bool, List[str]]:
        messages = []
        ok, msg = await self.check_global_rate_limit(user_id)
        messages.append(msg)
        if not ok:
            return False, messages
        ok, msg = await self.check_photo_hash(photo_path, user_id, route_id)
        messages.append(msg)
        if not ok:
            return False, messages
        ok, msg = await self.check_exif_date(photo_path)
        messages.append(msg)
        ok, msg = await self.check_timing(user_id, route_id, point_order)
        messages.append(msg)
        if not ok:
            return False, messages
        return True, messages