import logging
from decimal import Decimal
from typing import Optional
from sqlalchemy import text
from bot.models.token_transaction import PaymentMethod
from sqlalchemy.ext.asyncio import AsyncSession
logger = logging.getLogger(__name__)
async def award_quest_earnings(
    session: AsyncSession,
    progress_id: int,
    user_id: int,
    route_id: int,
    amount: Decimal,
    source: str,
) -> Decimal:
    if amount <= 0:
        return Decimal("0")
    row = await session.execute(
        text("SELECT max_earnings FROM routes WHERE id = :route_id"),
        {"route_id": route_id},
    )
    route_row = row.fetchone()
    max_earnings = route_row[0] if route_row and route_row[0] is not None else None
    row2 = await session.execute(
        text("SELECT total_earned FROM user_progress WHERE id = :pid"),
        {"pid": progress_id},
    )
    progress_row = row2.fetchone()
    total_earned = Decimal(str(progress_row[0])) if progress_row and progress_row[0] else Decimal("0")
    if max_earnings is not None:
        max_earnings = Decimal(str(max_earnings))
        remaining = max_earnings - total_earned
        if remaining <= 0:
            logger.info(f"[EARNINGS] user={user_id} route={route_id} limit reached ({total_earned}/{max_earnings}), source={source}")
            return Decimal("0")
        actual_amount = min(amount, remaining)
    else:
        actual_amount = amount
    from bot.repositories.token import TokenRepository
    token_repo = TokenRepository(session)
    await token_repo.deposit(
        user_id=user_id,
        amount=actual_amount,
        payment_method=PaymentMethod.SYSTEM,
        description=f"Earning: {source} (quest progress {progress_id})",
    )
    await session.execute(
        text("UPDATE user_progress SET total_earned = total_earned + :amt WHERE id = :pid"),
        {"amt": float(actual_amount), "pid": progress_id},
    )
    await session.commit()
    logger.info(f"[EARNINGS] user={user_id} route={route_id} awarded={actual_amount} source={source} total={total_earned + actual_amount}")
    return actual_amount