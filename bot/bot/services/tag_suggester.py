import re
from typing import List, Set
from sqlalchemy.ext.asyncio import AsyncSession
from bot.repositories.tag import TagRepository
class TagSuggester:
    KEYWORDS = {
        'istoriya': ['история', 'исторический', 'прошлое', 'древний', 'старинный', 'век', 'эпоха'],
        'arhitektura': ['архитектура', 'здание', 'сооружение', 'дом', 'постройка', 'фасад'],
        'iskusstvo': ['искусство', 'музей', 'галерея', 'картина', 'скульптура', 'выставка'],
        'razvlecheniya': ['развлечение', 'аттракцион', 'парк', 'игра', 'веселье'],
        'priroda': ['природа', 'парк', 'сад', 'река', 'озеро', 'лес', 'горы', 'пейзаж'],
        'religiya': ['религия', 'церковь', 'храм', 'собор', 'монастырь', 'часовня'],
        'sport': ['спорт', 'стадион', 'физкультура', 'активность', 'велосипед'],
        'eda': ['еда', 'ресторан', 'кафе', 'кухня', 'блюдо', 'вкусно'],
        'detskie': ['дети', 'детский', 'ребенок', 'малыш', 'семья'],
        'semeinye': ['семья', 'семейный', 'родители', 'вместе'],
    }
    def __init__(self, session: AsyncSession):
        self.session = session
        self.tag_repo = TagRepository(session)
    async def suggest_tags(self, description: str, duration_minutes: int = None) -> List[int]:
        suggested_tag_slugs: Set[str] = set()
        description_lower = description.lower()
        for slug, keywords in self.KEYWORDS.items():
            if any(keyword in description_lower for keyword in keywords):
                suggested_tag_slugs.add(slug)
        if duration_minutes:
            if duration_minutes <= 30:
                suggested_tag_slugs.add('do-30-min')
            elif duration_minutes <= 60:
                suggested_tag_slugs.add('30-60-min')
            elif duration_minutes <= 120:
                suggested_tag_slugs.add('1-2-hours')
            else:
                suggested_tag_slugs.add('2plus-hours')
        tag_ids = []
        for slug in suggested_tag_slugs:
            tag = await self.tag_repo.get_by_slug(slug)
            if tag:
                tag_ids.append(tag.id)
        return tag_ids
    async def suggest_difficulty(self, description: str) -> int:
        description_lower = description.lower()
        easy_keywords = ['легкий', 'простой', 'короткий', 'недалеко', 'близко']
        hard_keywords = ['сложный', 'трудный', 'долгий', 'длинный', 'экстремальный']
        if any(keyword in description_lower for keyword in hard_keywords):
            return 3
        elif any(keyword in description_lower for keyword in easy_keywords):
            return 1
        else:
            return 2