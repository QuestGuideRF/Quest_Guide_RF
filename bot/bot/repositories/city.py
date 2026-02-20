from typing import Optional
from typing import List, Optional
from sqlalchemy import select
from typing import List, Optional
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.city import City
from bot.repositories.base import BaseRepository
class CityRepository(BaseRepository[City]):
    def __init__(self, session: AsyncSession):
        super().__init__(City, session)
    async def get_by_name(self, name: str) -> Optional[City]:
        result = await self.session.execute(
            select(City).where(City.name == name)
        )
        return result.scalar_one_or_none()
    async def get_active(self) -> List[City]:
        result = await self.session.execute(
            select(City).where(City.is_active == True)
        )
        return list(result.scalars().all())