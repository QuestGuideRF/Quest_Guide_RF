from typing import Optional
<<<<<<< HEAD
from sqlalchemy import select, func
=======
from sqlalchemy import select
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User, UserRole
from bot.repositories.base import BaseRepository
class UserRepository(BaseRepository[User]):
    def __init__(self, session: AsyncSession):
        super().__init__(User, session)
    async def get_by_telegram_id(self, telegram_id: int) -> Optional[User]:
        result = await self.session.execute(
            select(User).where(User.telegram_id == telegram_id)
        )
        return result.scalar_one_or_none()
    async def get_or_create(
        self,
        telegram_id: int,
        username: Optional[str] = None,
        first_name: Optional[str] = None,
        last_name: Optional[str] = None,
    ) -> User:
        user = await self.get_by_telegram_id(telegram_id)
        if not user:
            user = await self.create(
                telegram_id=telegram_id,
                username=username,
                first_name=first_name,
                last_name=last_name,
                role=UserRole.USER,
            )
        return user
    async def set_role(self, telegram_id: int, role: UserRole) -> Optional[User]:
        user = await self.get_by_telegram_id(telegram_id)
        if user:
            user.role = role
            await self.session.commit()
            await self.session.refresh(user)
        return user
    async def is_admin(self, telegram_id: int) -> bool:
        user = await self.get_by_telegram_id(telegram_id)
        return user and user.role == UserRole.ADMIN
    async def is_moderator_or_admin(self, telegram_id: int) -> bool:
        user = await self.get_by_telegram_id(telegram_id)
<<<<<<< HEAD
        return user and user.role in (UserRole.MODERATOR, UserRole.ADMIN)
    async def count_referred(self, referrer_user_id: int) -> int:
        result = await self.session.execute(
            select(func.count(User.id)).where(User.referred_by_id == referrer_user_id)
        )
        return result.scalar() or 0
=======
        return user and user.role in (UserRole.MODERATOR, UserRole.ADMIN)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
