import logging
from decimal import Decimal
from typing import Optional, Tuple
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
from bot.models.user import User
from bot.models.referral_level import ReferralLevel, RewardType
from bot.models.token_transaction import PaymentMethod
from bot.repositories.token import TokenRepository
from bot.repositories.referral import ReferralRepository
from bot.services.platform_settings import PlatformSettingsService
logger = logging.getLogger(__name__)
class ReferralService:
    def __init__(self, session: AsyncSession):
        self.session = session
        self.referral_repo = ReferralRepository(session)
        self.token_repo = TokenRepository(session)
        self.settings = PlatformSettingsService(session)
    async def process_purchase(
        self,
        buyer: User,
        route_id: int,
        route_price: Decimal
    ) -> Tuple[bool, Optional[str], Optional[Decimal]]:
        if not buyer.referred_by_id:
            return False, None, None
        already_paid = await self.referral_repo.check_referral_paid(
            buyer.referred_by_id, buyer.id
        )
        if already_paid:
            return False, "already_counted", None
        referrer = await self.session.get(User, buyer.referred_by_id)
        if not referrer:
            return False, "referrer_not_found", None
        current_level = referrer.referral_level
        reward_amount = await self._calculate_reward(referrer, current_level)
        if reward_amount and reward_amount > 0:
            await self.token_repo.deposit(
                user_id=referrer.id,
                amount=reward_amount,
                payment_method=PaymentMethod.SYSTEM,
                description=f"Реферальное вознаграждение (ур.{current_level or 1})"
            )
        await self.referral_repo.add_referral_reward(
            user_id=referrer.id,
            referral_id=buyer.id,
            level=current_level or 1,
            reward_type="tokens",
            reward_amount=reward_amount
        )
        await self.referral_repo.update_user_referral_stats(
            user_id=referrer.id,
            add_paid_referral=True,
            add_earnings=reward_amount if reward_amount is not None else Decimal("0")
        )
        new_level = await self.referral_repo.check_and_upgrade_level(referrer.id)
        if new_level:
            await self._grant_level_reward(referrer, new_level)
            await self._grant_achievement(referrer.id, new_level.level)
        return True, "success", reward_amount
    async def _calculate_reward(self, user: User, level: int) -> Decimal:
        if level >= 1:
            tokens = await self.settings.get_referral_level_tokens(1)
            return tokens
        result = await self.session.execute(
            text("SELECT value FROM platform_settings WHERE `key` = 'referral_reward_amount'")
        )
        row = result.fetchone()
        return Decimal(row[0]) if row else Decimal("10")
    async def _grant_level_reward(self, user: User, level: ReferralLevel):
        rt = (level.reward_type or "").lower()
        if rt == RewardType.DISCOUNT_CODE.value:
            await self._create_promo_code(user, level)
        elif rt == RewardType.FREE_ROUTE.value:
            pass
        elif rt == RewardType.SPECIAL.value:
            await self.referral_repo.update_user_referral_stats(
                user_id=user.id,
                set_partner=True
            )
    async def _create_promo_code(self, user: User, level: ReferralLevel):
        import random
        import string
        code = "REF" + ''.join(random.choices(string.ascii_uppercase + string.digits, k=6))
        discount = int(level.reward_value) if level.reward_value else 15
        await self.session.execute(
            text("""
                INSERT INTO promo_codes (code, discount_type, discount_value, max_uses, times_used, is_active, created_by_user_id)
                VALUES (:code, 'percent', :discount, 1, 0, 1, :user_id)
            """),
            {"code": code, "discount": discount, "user_id": user.id}
        )
        await self.session.commit()
    async def _grant_achievement(self, user_id: int, level: int):
        achievement_map = {
            1: 11,
            2: 12,
            3: 13,
            4: 14
        }
        achievement_id = achievement_map.get(level)
        if not achievement_id:
            return
        result = await self.session.execute(
            text("SELECT id FROM user_achievements WHERE user_id = :user_id AND achievement_id = :ach_id"),
            {"user_id": user_id, "ach_id": achievement_id}
        )
        if result.fetchone():
            return
        await self.session.execute(
            text("INSERT INTO user_achievements (user_id, achievement_id) VALUES (:user_id, :ach_id)"),
            {"user_id": user_id, "ach_id": achievement_id}
        )
        await self.session.commit()
    async def get_user_referral_info(self, user_id: int) -> dict:
        user = await self.session.get(User, user_id)
        if not user:
            return {}
        stats = await self.referral_repo.get_user_referral_stats(user_id)
        next_level = await self.referral_repo.get_next_level_info(user_id)
        levels = await self.referral_repo.get_all_levels()
        return {
            "user": user,
            "stats": stats,
            "next_level": next_level,
            "levels": levels
        }