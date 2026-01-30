import os
import logging
from PIL import Image, ImageDraw, ImageFont
from datetime import datetime
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, text
logger = logging.getLogger(__name__)
CERTIFICATE_TEMPLATE = os.path.join(os.path.dirname(__file__), '../../..', 'assets/certificate_foto/certificate.png')
FONT_PATH = os.path.join(os.path.dirname(__file__), '../../..', 'assets/fonts/DejaVuSans.ttf')
OUTPUT_DIR = os.path.join(os.path.dirname(__file__), '../../..', 'assets/certificate_foto/generated')
ALT_FONTS = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/dejavu/DejaVuSans.ttf',
]
class CertificateService:
    def __init__(self, session: AsyncSession):
        self.session = session
    def _get_font_path(self):
        if os.path.exists(FONT_PATH):
            return FONT_PATH
        for font in ALT_FONTS:
            if os.path.exists(font):
                return font
        return None
    def _draw_centered_text(self, draw, text, cx, y, font, color):
        bbox = draw.textbbox((0, 0), text, font=font)
        tw = bbox[2] - bbox[0]
        x = cx - (tw / 2)
        draw.text((x, y), text, font=font, fill=color)
    async def get_progress_data(self, progress_id: int):
        query = text()
        result = await self.session.execute(query, {"progress_id": progress_id})
        row = result.fetchone()
        if row:
            return dict(row._mapping)
        return None
    async def certificate_exists(self, progress_id: int) -> bool:
        query = text("SELECT id FROM certificates WHERE progress_id = :progress_id LIMIT 1")
        result = await self.session.execute(query, {"progress_id": progress_id})
        return result.fetchone() is not None
    def generate_certificate(self, data: dict, language: str = 'ru') -> str | None:
        try:
            template_path = os.path.abspath(CERTIFICATE_TEMPLATE)
            if not os.path.exists(template_path):
                logger.error(f"Certificate template not found: {template_path}")
                return None
            font_path = self._get_font_path()
            if not font_path:
                logger.error("No font found for certificate generation")
                return None
            img = Image.open(template_path)
            draw = ImageDraw.Draw(img)
            w, h = img.size
            cx = w / 2
            dark = (60, 50, 40)
            gray = (100, 90, 80)
            user_name = f"{data.get('first_name', '')} {data.get('last_name', '')}".strip()
            if not user_name:
                user_name = "Участник" if language == 'ru' else "Participant"
            if language == 'en' and data.get('route_name_en'):
                route_name = data['route_name_en']
            else:
                route_name = data.get('route_name', 'Квест' if language == 'ru' else 'Quest')
            mins = int(data.get('minutes', 0) or 0)
            hrs = mins // 60
            m = mins % 60
            if language == 'ru':
                time_text = f"Время прохождения: {f'{hrs} ч. ' if hrs > 0 else ''}{m} мин."
                dist = float(data.get('distance', 0) or 0)
                distance_text = f"Расстояние: {dist:.1f} км"
            else:
                time_text = f"Completion time: {f'{hrs} h ' if hrs > 0 else ''}{m} min"
                dist = float(data.get('distance', 0) or 0)
                distance_text = f"Distance: {dist:.1f} km"
            try:
                font_large = ImageFont.truetype(font_path, 48)
                font_medium = ImageFont.truetype(font_path, 36)
                font_small = ImageFont.truetype(font_path, 28)
            except Exception as e:
                logger.error(f"Failed to load font: {e}")
                return None
            y1 = h * 0.32
            gap = h * 0.08
            self._draw_centered_text(draw, user_name, cx, y1, font_large, dark)
            self._draw_centered_text(draw, route_name, cx, y1 + gap, font_medium, gray)
            self._draw_centered_text(draw, time_text, cx, y1 + gap * 2, font_small, gray)
            self._draw_centered_text(draw, distance_text, cx, y1 + gap * 2.7, font_small, gray)
            user_dir = os.path.join(os.path.abspath(OUTPUT_DIR), str(data['user_id']))
            os.makedirs(user_dir, exist_ok=True)
            filename = f"cert_{data['progress_id']}_{language}_{int(datetime.now().timestamp())}.png"
            filepath = os.path.join(user_dir, filename)
            img.save(filepath, 'PNG')
            relative_path = f"/assets/certificate_foto/generated/{data['user_id']}/{filename}"
            logger.info(f"Certificate generated: {relative_path}")
            return relative_path
        except Exception as e:
            logger.error(f"Failed to generate certificate: {e}", exc_info=True)
            return None
    async def save_certificate(self, user_id: int, route_id: int, progress_id: int, language: str, file_path: str):
        query = text()
        await self.session.execute(query, {
            "user_id": user_id,
            "route_id": route_id,
            "progress_id": progress_id,
            "language": language,
            "file_path": file_path
        })
        await self.session.commit()
    async def create_certificates(self, progress_id: int) -> dict:
        if await self.certificate_exists(progress_id):
            logger.info(f"Certificates already exist for progress {progress_id}")
            return {"ru": None, "en": None}
        data = await self.get_progress_data(progress_id)
        if not data:
            logger.error(f"Progress data not found for {progress_id}")
            return {"ru": None, "en": None}
        result = {"ru": None, "en": None}
        for lang in ['ru', 'en']:
            path = self.generate_certificate(data, lang)
            if path:
                await self.save_certificate(
                    data['user_id'],
                    data['route_id'],
                    progress_id,
                    lang,
                    path
                )
                result[lang] = path
        return result