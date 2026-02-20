from typing import Optional, List, Any
from decimal import Decimal
from datetime import datetime
from sqlalchemy import select, and_, func, text
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from bot.models.referral_level import ReferralLevel, RewardType
from bot.models.referral_reward import ReferralReward
from bot.models.token_transaction import TokenTransaction, TransactionType
from bot.repositories.base import BaseRepository
def _level_row_to_obj(row: Any) -> Any:
    def _get(r, key: str, default=None):
        if hasattr(r, "_mapping"):
            return r._mapping.get(key, default)
        return getattr(r, key, default)
    return type("ReferralLevelRow", (), {
        "id": _get(row, "id"),
        "level": _get(row, "level"),
        "name": _get(row, "name") or "",
        "name_en": _get(row, "name_en"),
        "description": _get(row, "description"),
        "description_en": _get(row, "description_en"),
        "required_referrals": _get(row, "required_referrals") or 0,
        "reward_type": _get(row, "reward_type") or "",
        "reward_value": _get(row, "reward_value"),
        "icon": _get(row, "icon") or "🎁",
        "is_active": _get(row, "is_active") if _get(row, "is_active") is not None else True,
    })()
class ReferralRepository(BaseRepository[ReferralLevel]):
    def __init__(self, session: AsyncSession):
        super().__init__(ReferralLevel, session)
    async def get_all_levels(self) -> List[Any]:
        result = await self.session.execute(
            text("""
                SELECT id, level, name, name_en, description, description_en,
                       required_referrals, reward_type, reward_value, icon, is_active
                FROM referral_levels WHERE is_active = 1 ORDER BY level
            """)
        )
        rows = result.fetchall()
        return [_level_row_to_obj(row) for row in rows]
    async def get_level(self, level: int) -> Optional[Any]:
        result = await self.session.execute(
            text("""
                SELECT id, level, name, name_en, description, description_en,
                       required_referrals, reward_type, reward_value, icon, is_active
                FROM referral_levels WHERE level = :level
            """),
            {"level": level}
        )
        row = result.fetchone()
        return _level_row_to_obj(row) if row else None
    async def get_user_level(self, user_id: int) -> int:
        user = await self.session.get(User, user_id)
        if not user:
            return 0
        return user.referral_level
    async def get_user_referral_stats(self, user_id: int) -> dict:
        user = await self.session.get(User, user_id)
        if not user:
            return {
                "level": 0,
                "paid_referrals": 0,
                "total_earnings": Decimal("0.00"),
                "is_partner": False
            }
        return {
            "level": user.referral_level,
            "paid_referrals": user.paid_referrals_count,
            "total_earnings": user.referral_earnings,
            "is_partner": user.is_partner
        }
    async def get_referrals(self, user_id: int) -> List[User]:
        result = await self.session.execute(
            select(User).where(User.referred_by_id == user_id)
        )
        return list(result.scalars().all())
    async def get_paid_referrals_count(self, user_id: int) -> int:
        result = await self.session.execute(
            select(func.count(ReferralReward.id.distinct())).where(
                ReferralReward.user_id == user_id
            )
        )
        return result.scalar() or 0
    async def check_referral_paid(self, referrer_id: int, referral_id: int) -> bool:
        result = await self.session.execute(
            select(ReferralReward).where(
                and_(
                    ReferralReward.user_id == referrer_id,
                    ReferralReward.referral_id == referral_id
                )
            )
        )
        return result.scalar_one_or_none() is not None
    async def add_referral_reward(
        self,
        user_id: int,
        referral_id: int,
        level: int,
        reward_type: str,
        reward_amount: Decimal = None,
        promo_code_id: int = None,
        route_id: int = None
    ) -> ReferralReward:
        reward = ReferralReward(
            user_id=user_id,
            referral_id=referral_id,
            level=level,
            reward_type=reward_type,
            reward_amount=reward_amount,
            promo_code_id=promo_code_id,
            route_id=route_id
        )
        self.session.add(reward)
        await self.session.commit()
        await self.session.refresh(reward)
        return reward
    async def update_user_referral_stats(
        self,
        user_id: int,
        new_level: int = None,
        add_paid_referral: bool = False,
        add_earnings: Decimal = None,
        set_partner: bool = False
    ):
        user = await self.session.get(User, user_id)
        if not user:
            return
        if new_level is not None:
            user.referral_level = new_level
        if add_paid_referral:
            user.paid_referrals_count += 1
        if add_earnings:
            user.referral_earnings += add_earnings
        if set_partner:
            user.is_partner = True
        await self.session.commit()
    async def get_next_level_info(self, user_id: int) -> Optional[dict]:
        user = await self.session.get(User, user_id)
        if not user:
            return None
        current_level = user.referral_level
        next_level_num = current_level + 1
        next_level = await self.get_level(next_level_num)
        if not next_level:
            return None
        return {
            "level": next_level.level,
            "name": next_level.name,
            "name_en": next_level.name_en,
            "required_referrals": next_level.required_referrals,
            "current_referrals": user.paid_referrals_count,
            "remaining": max(0, next_level.required_referrals - user.paid_referrals_count),
            "reward_type": next_level.reward_type,
            "reward_value": next_level.reward_value,
            "icon": next_level.icon
        }
    async def check_and_upgrade_level(self, user_id: int) -> Optional[ReferralLevel]:
        user = await self.session.get(User, user_id)
        if not user:
            return None
        levels = await self.get_all_levels()
        current_level = user.referral_level
        paid_count = user.paid_referrals_count
        new_level = None
        for level in levels:
            if level.level > current_level and paid_count >= level.required_referrals:
                new_level = level
        if new_level:
            user.referral_level = new_level.level
            if new_level.level == 4:
                user.is_partner = True
            await self.session.commit()
            return new_level
        return None
    async def get_rewards_history(self, user_id: int, limit: int = 20) -> List[ReferralReward]:
        result = await self.session.execute(
            select(ReferralReward)
            .where(ReferralReward.user_id == user_id)
            .order_by(ReferralReward.created_at.desc())
            .limit(limit)
        )
        return list(result.scalars().all())
    async def update_level_settings(
        self,
        level: int,
        required_referrals: int = None,
        reward_value: Decimal = None,
        name: str = None,
        description: str = None,
        is_active: bool = None
    ) -> Optional[Any]:
        level_obj = await self.get_level(level)
        if not level_obj:
            return None
        await self.session.execute(
            text("""
                UPDATE referral_levels SET
                    required_referrals = COALESCE(:required_referrals, required_referrals),
                    reward_value = COALESCE(:reward_value, reward_value),
                    name = COALESCE(:name, name),
                    description = COALESCE(:description, description),
                    is_active = COALESCE(:is_active, is_active)
                WHERE level = :level
            """),
            {
                "level": level,
                "required_referrals": required_referrals,
                "reward_value": float(reward_value) if reward_value is not None else None,
                "name": name,
                "description": description,
                "is_active": 1 if is_active is True else (0 if is_active is False else None),
            }
        )
        await self.session.commit()
        return await self.get_level(level)