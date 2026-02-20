from typing import List
from sqlalchemy import select, delete
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.route_tag import RouteTag
from bot.models.tag import Tag
from bot.repositories.base import BaseRepository
class RouteTagRepository(BaseRepository[RouteTag]):
    def __init__(self, session: AsyncSession):
        super().__init__(RouteTag, session)
    async def get_tags_for_route(self, route_id: int) -> List[Tag]:
        result = await self.session.execute(
            select(Tag)
            .join(RouteTag, RouteTag.tag_id == Tag.id)
            .where(RouteTag.route_id == route_id)
            .order_by(Tag.type, Tag.name)
        )
        return list(result.scalars().all())
    async def get_routes_for_tag(self, tag_id: int) -> List[int]:
        result = await self.session.execute(
            select(RouteTag.route_id).where(RouteTag.tag_id == tag_id)
        )
        return [row[0] for row in result.all()]
    async def add_tag_to_route(self, route_id: int, tag_id: int) -> RouteTag:
        route_tag = RouteTag(route_id=route_id, tag_id=tag_id)
        self.session.add(route_tag)
        await self.session.flush()
        return route_tag
    async def remove_tag_from_route(self, route_id: int, tag_id: int):
        await self.session.execute(
            delete(RouteTag)
            .where(RouteTag.route_id == route_id, RouteTag.tag_id == tag_id)
        )
    async def set_tags_for_route(self, route_id: int, tag_ids: List[int]):
        await self.session.execute(
            delete(RouteTag).where(RouteTag.route_id == route_id)
        )
        for tag_id in tag_ids:
            route_tag = RouteTag(route_id=route_id, tag_id=tag_id)
            self.session.add(route_tag)
        await self.session.flush()