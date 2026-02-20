from typing import List, Dict, Set
from sqlalchemy import select, func, and_
from bot.models.route import Route
from bot.models.route_tag import RouteTag
from bot.models.tag import Tag
class RouteFilterMixin:
    async def get_routes_by_filters(self, city_id: int, filters: Dict[str, Set[int]]) -> List[Route]:
        if not filters or all(not v for v in filters.values()):
            return await self.get_by_city(city_id)
        all_tag_ids = set()
        for tag_ids in filters.values():
            all_tag_ids.update(tag_ids)
        if not all_tag_ids:
            return await self.get_by_city(city_id)
        query = (
            select(Route)
            .distinct()
            .join(RouteTag, RouteTag.route_id == Route.id)
            .join(Tag, Tag.id == RouteTag.tag_id)
            .where(
                and_(
                    Route.city_id == city_id,
                    Route.is_active == True,
                    Tag.id.in_(all_tag_ids)
                )
            )
            .group_by(Route.id)
            .having(func.count(Tag.id) >= len(all_tag_ids))
            .order_by(Route.name)
        )
        result = await self.session.execute(query)
        return list(result.scalars().all())
    async def get_routes_with_tags(self, city_id: int) -> List[tuple]:
        from sqlalchemy.orm import selectinload
        result = await self.session.execute(
            select(Route)
            .options(selectinload(Route.route_tags).selectinload(RouteTag.tag))
            .where(Route.city_id == city_id, Route.is_active == True)
            .order_by(Route.name)
        )
        routes = result.scalars().all()
        routes_with_tags = []
        for route in routes:
            tags = [rt.tag for rt in route.route_tags]
            routes_with_tags.append((route, tags))
        return routes_with_tags