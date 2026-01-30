from typing import Generic, TypeVar, Type, Optional, List
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.base import Base
ModelType = TypeVar("ModelType", bound=Base)
class BaseRepository(Generic[ModelType]):
    def __init__(self, model: Type[ModelType], session: AsyncSession):
        self.model = model
        self.session = session
    async def get(self, id: int) -> Optional[ModelType]:
        return await self.session.get(self.model, id)
    async def get_all(self, skip: int = 0, limit: int = 100) -> List[ModelType]:
        result = await self.session.execute(
            select(self.model).offset(skip).limit(limit)
        )
        return list(result.scalars().all())
    async def create(self, **kwargs) -> ModelType:
        instance = self.model(**kwargs)
        self.session.add(instance)
        await self.session.commit()
        await self.session.refresh(instance)
        return instance
    async def update(self, id: int, **kwargs) -> Optional[ModelType]:
        instance = await self.get(id)
        if instance:
            for key, value in kwargs.items():
                setattr(instance, key, value)
            await self.session.commit()
            await self.session.refresh(instance)
        return instance
    async def delete(self, id: int) -> bool:
        instance = await self.get(id)
        if instance:
            await self.session.delete(instance)
            await self.session.commit()
            return True
        return False