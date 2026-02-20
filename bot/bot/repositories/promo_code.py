from typing import Optional, Tuple
from sqlalchemy import select, and_, or_, func
from sqlalchemy.ext.asyncio import AsyncSession
from datetime import datetime
from bot.models.promo_code import PromoCode, DiscountType
from bot.models.promo_code_use import PromoCodeUse
from bot.repositories.base import BaseRepository
class PromoCodeRepository(BaseRepository[PromoCode]):
    def __init__(self, session: AsyncSession):
        super().__init__(PromoCode, session)
    async def get_by_code(self, code: str) -> Optional[PromoCode]:
        result = await self.session.execute(
            select(PromoCode).where(PromoCode.code == code.upper())
        )
        return result.scalar_one_or_none()
    async def validate_promo_code(
        self,
        code: str,
        user_id: int,
        route_id: Optional[int] = None
    ) -> Tuple[bool, Optional[str], Optional[PromoCode]]:
        import logging
        logger = logging.getLogger(__name__)
        promo_code = await self.get_by_code(code)
        if not promo_code:
            logger.debug(f"Промокод {code} не найден")
            return False, "promo_code_not_found", None
        logger.info(f"Проверка промокода {code}: is_active={promo_code.is_active}, route_id={promo_code.route_id}, user_route_id={route_id}, user_id={user_id}")
        logger.info(f"Детали промокода: valid_from={promo_code.valid_from}, valid_until={promo_code.valid_until}, max_uses={promo_code.max_uses}, used_count={promo_code.used_count}")
        if not promo_code.is_active:
            logger.info(f"Промокод {code} неактивен")
            return False, "promo_code_invalid", None
        now = datetime.utcnow()
        if promo_code.valid_from and promo_code.valid_from > now:
            logger.info(f"Промокод {code} еще не действителен (valid_from={promo_code.valid_from}, now={now})")
            return False, "promo_code_invalid", None
        if promo_code.valid_until and promo_code.valid_until < now:
            logger.info(f"Промокод {code} истек (valid_until={promo_code.valid_until}, now={now})")
            return False, "promo_code_expired", None
        if promo_code.max_uses and promo_code.used_count >= promo_code.max_uses:
            logger.info(f"Промокод {code} исчерпан (used_count={promo_code.used_count}, max_uses={promo_code.max_uses})")
            return False, "promo_code_invalid", None
        result = await self.session.execute(
            select(PromoCodeUse).where(
                and_(
                    PromoCodeUse.promo_code_id == promo_code.id,
                    PromoCodeUse.user_id == user_id
                )
            )
        )
        if result.scalar_one_or_none():
            logger.debug(f"Пользователь {user_id} уже использовал промокод {code}")
            return False, "promo_code_already_used", None
<<<<<<< HEAD
        if promo_code.route_id is not None and route_id is not None:
=======
        if promo_code.route_id is not None:
            if route_id is None:
                logger.debug(f"Промокод {code} привязан к маршруту {promo_code.route_id}, но пользователь не выбрал маршрут")
                return False, "promo_code_wrong_route", None
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            if promo_code.route_id != route_id:
                logger.debug(f"Промокод {code} привязан к маршруту {promo_code.route_id}, но пользователь выбрал маршрут {route_id}")
                return False, "promo_code_wrong_route", None
        logger.info(f"Промокод {code} валиден - все проверки пройдены")
        return True, None, promo_code
    async def apply_promo_code(
        self,
        promo_code: PromoCode,
        user_id: int,
        route_id: int,
        original_price: int
    ) -> Tuple[int, float]:
        discount_amount = 0.0
        discount_type_value = promo_code.discount_type.value.lower() if hasattr(promo_code.discount_type, 'value') else str(promo_code.discount_type).lower()
        if discount_type_value == "percentage":
            discount_amount = original_price * (promo_code.discount_value / 100)
            final_price = max(0, int(original_price - discount_amount))
        elif discount_type_value == "fixed":
            discount_amount = float(promo_code.discount_value)
            final_price = max(0, int(original_price - discount_amount))
        elif discount_type_value == "free_route":
            discount_amount = original_price
            final_price = 0
        promo_code.used_count += 1
        promo_use = PromoCodeUse(
            promo_code_id=promo_code.id,
            user_id=user_id,
            route_id=route_id,
            discount_amount=discount_amount
        )
        self.session.add(promo_use)
        await self.session.commit()
        await self.session.refresh(promo_code)
<<<<<<< HEAD
        return final_price, discount_amount
    async def get_uses_by_user(self, user_id: int):
        from sqlalchemy.orm import selectinload
        result = await self.session.execute(
            select(PromoCodeUse)
            .where(PromoCodeUse.user_id == user_id)
            .options(selectinload(PromoCodeUse.promo_code))
            .order_by(PromoCodeUse.used_at.desc())
        )
        return list(result.scalars().all())
=======
        return final_price, discount_amount
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
