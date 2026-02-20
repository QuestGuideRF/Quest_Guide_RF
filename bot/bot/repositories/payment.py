from typing import Optional
<<<<<<< HEAD
from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.payment import Payment, PaymentStatus
from bot.models.user_progress import UserProgress, ProgressStatus
from bot.models.user import User
from bot.models.promo_code_use import PromoCodeUse
=======
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.payment import Payment, PaymentStatus
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from bot.repositories.base import BaseRepository
class PaymentRepository(BaseRepository[Payment]):
    def __init__(self, session: AsyncSession):
        super().__init__(Payment, session)
    async def create_payment(
        self,
        user_id: int,
        route_id: int,
        amount: int,
    ) -> Payment:
        return await self.create(
            user_id=user_id,
            route_id=route_id,
            amount=amount,
            currency="RUB",
            status=PaymentStatus.PENDING,
        )
    async def mark_success(
        self,
        payment_id: int,
        telegram_charge_id: str,
        provider_charge_id: str,
    ) -> Optional[Payment]:
        payment = await self.get(payment_id)
        if payment:
            payment.status = PaymentStatus.SUCCESS
            payment.telegram_payment_charge_id = telegram_charge_id
            payment.provider_payment_charge_id = provider_charge_id
            await self.session.commit()
            await self.session.refresh(payment)
        return payment
    async def has_paid_for_route(self, user_id: int, route_id: int) -> bool:
<<<<<<< HEAD
        pay_result = await self.session.execute(
            select(Payment.id).where(
                Payment.user_id == user_id,
                Payment.route_id == route_id,
                Payment.status == PaymentStatus.SUCCESS,
            ).limit(1)
        )
        has_payment = pay_result.scalars().first() is not None
        if not has_payment:
            promo_use = await self.session.execute(
                select(PromoCodeUse.id).where(
                    PromoCodeUse.user_id == user_id,
                    PromoCodeUse.route_id == route_id,
                ).limit(1)
            )
            if promo_use.scalars().first() is None:
                return False
        completed = await self.session.execute(
            select(UserProgress.id).where(
                UserProgress.user_id == user_id,
                UserProgress.route_id == route_id,
                UserProgress.status == ProgressStatus.COMPLETED,
            ).limit(1)
        )
        return completed.scalars().first() is None
    async def count_successful_by_referred_users(self, referrer_user_id: int) -> int:
        subq = select(User.id).where(User.referred_by_id == referrer_user_id)
        result = await self.session.execute(
            select(func.count(Payment.id)).where(
                Payment.user_id.in_(subq),
                Payment.status == PaymentStatus.SUCCESS,
            )
        )
        return result.scalar() or 0
=======
        result = await self.session.execute(
            select(Payment).where(
                Payment.user_id == user_id,
                Payment.route_id == route_id,
                Payment.status == PaymentStatus.SUCCESS,
            )
        )
        return result.scalar_one_or_none() is not None
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
