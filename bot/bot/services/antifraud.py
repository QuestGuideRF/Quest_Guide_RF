import json
import logging
from typing import List, Tuple, Dict, Any, Optional
import hashlib
from datetime import datetime, timedelta
from PIL import Image
from PIL.ExifTags import TAGS
from bot.services.antifraud_fix import simple_cache
logger = logging.getLogger(__name__)
PERCEPTUAL_HASH_THRESHOLD = 10
class AntiFraudService:
    def __init__(self):
        self.redis = simple_cache
    def _perceptual_hash(self, photo_path: str) -> str:
        try:
            resample = getattr(Image, "Resampling", Image).LANCZOS
            img = Image.open(photo_path).convert("L").resize((8, 8), resample)
            pixels = list(img.getdata())
            mean = sum(pixels) / len(pixels)
            bits = 0
            for i, p in enumerate(pixels):
                if p > mean:
                    bits |= 1 << (63 - i)
            return f"{bits:016x}"
        except Exception as e:
            logger.warning("antifraud perceptual hash failed: %s", e)
            return ""
    @staticmethod
    def _hamming_hex(h1: str, h2: str) -> int:
        if len(h1) != 16 or len(h2) != 16:
            return 999
        n1, n2 = int(h1, 16), int(h2, 16)
        x = n1 ^ n2
        return bin(x).count("1")
    def _phash_matches_stored(self, phash: str, stored_list: list) -> bool:
        if not phash or len(phash) != 16:
            return False
        for stored in stored_list:
            if isinstance(stored, str) and self._hamming_hex(phash, stored) <= PERCEPTUAL_HASH_THRESHOLD:
                return True
        return False
    async def check_photo_hash(
        self,
        photo_path: str,
        user_id: int,
        route_id: int,
        session: Any = None,
        progress: Any = None,
    ) -> Tuple[bool, str, Optional[str]]:
        raw_hash = self._calculate_photo_hash(photo_path)
        key_raw = f"photo_hash:{user_id}:{route_id}:{raw_hash}"
        if await self.redis.exists(key_raw):
            return False, "Это фото уже использовалось в данном маршруте", None
        await self.redis.setex(key_raw, 86400, "1")
        phash = self._perceptual_hash(photo_path)
        key_phash_list = f"photo_phash_list:{user_id}:{route_id}"
        cached_json = await self.redis.get(key_phash_list)
        try:
            cached_list = json.loads(cached_json) if cached_json else []
        except (json.JSONDecodeError, TypeError):
            cached_list = []
        if phash and self._phash_matches_stored(phash, cached_list):
            return False, "Это фото уже использовалось в данном маршруте", None
        if progress and phash:
            stored_raw = getattr(progress, "photo_hashes", None) or "[]"
            try:
                stored_list = json.loads(stored_raw) if isinstance(stored_raw, str) else (stored_raw or [])
            except (json.JSONDecodeError, TypeError):
                stored_list = []
            if self._phash_matches_stored(phash, stored_list):
                return False, "Это фото уже использовалось в данном маршруте", None
        return True, "Фото уникально", (phash or None)
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
                    except Exception as e:
                        logger.debug("antifraud EXIF: не удалось распарсить дату '%s': %s", value, e)
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
        session: Any = None,
        progress: Any = None,
    ) -> Tuple[bool, List[str]]:
        messages = []
        ok, msg = await self.check_global_rate_limit(user_id)
        messages.append(msg)
        if not ok:
            return False, messages
        ok, msg, phash_to_store = await self.check_photo_hash(
            photo_path, user_id, route_id, session=session, progress=progress
        )
        messages.append(msg)
        if not ok:
            return False, messages
        ok, msg = await self.check_timing(user_id, route_id, point_order)
        messages.append(msg)
        if not ok:
            return False, messages
        if phash_to_store:
            if progress is not None:
                stored_raw = getattr(progress, "photo_hashes", None) or "[]"
                try:
                    stored_list = json.loads(stored_raw) if isinstance(stored_raw, str) else (stored_raw or [])
                except (json.JSONDecodeError, TypeError):
                    stored_list = []
                stored_list.append(phash_to_store)
                progress.photo_hashes = json.dumps(stored_list)
            key_phash_list = f"photo_phash_list:{user_id}:{route_id}"
            cached_json = await self.redis.get(key_phash_list)
            try:
                cached_list = json.loads(cached_json) if cached_json else []
            except (json.JSONDecodeError, TypeError):
                cached_list = []
            cached_list.append(phash_to_store)
            await self.redis.setex(key_phash_list, 86400, json.dumps(cached_list))
        return True, messages