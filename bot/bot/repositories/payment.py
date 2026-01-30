from typing import Optional
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.payment import Payment, PaymentStatus
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
        result = await self.session.execute(
            select(Payment).where(
                Payment.user_id == user_id,
                Payment.route_id == route_id,
                Payment.status == PaymentStatus.SUCCESS,
            )
        )
        return result.scalar_one_or_none() is not None