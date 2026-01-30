from typing import List, Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.tag import Tag
from bot.repositories.base import BaseRepository
class TagRepository(BaseRepository[Tag]):
    def __init__(self, session: AsyncSession):
        super().__init__(Tag, session)
    async def get_by_slug(self, slug: str) -> Optional[Tag]:
        result = await self.session.execute(
            select(Tag).where(Tag.slug == slug)
        )
        return result.scalars().first()
    async def get_by_type(self, tag_type: str) -> List[Tag]:
        result = await self.session.execute(
            select(Tag).where(Tag.type == tag_type).order_by(Tag.name)
        )
        return list(result.scalars().all())
    async def get_popular_tags(self, limit: int = 10) -> List[tuple]:
        from bot.models.route_tag import RouteTag
        from sqlalchemy import func
        result = await self.session.execute(
            select(Tag, func.count(RouteTag.id).label("usage_count"))
            .join(RouteTag, RouteTag.tag_id == Tag.id)
            .group_by(Tag.id)
            .order_by(func.count(RouteTag.id).desc())
            .limit(limit)
        )
        return list(result.all())
    async def search(self, query: str) -> List[Tag]:
        result = await self.session.execute(
            select(Tag)
            .where(Tag.name.ilike(f"%{query}%"))
            .order_by(Tag.name)
        )
        return list(result.scalars().all())