from typing import Optional, List, Tuple
from decimal import Decimal
from datetime import datetime, timedelta
<<<<<<< HEAD
from sqlalchemy import select, and_, or_, func
=======
from sqlalchemy import select, and_, or_
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from bot.models.token_balance import TokenBalance
from bot.models.token_transaction import TokenTransaction, TransactionType, PaymentMethod, TransactionStatus
from bot.models.token_deposit import TokenDeposit, DepositStatus
from bot.models.user_search_limit import UserSearchLimit
from bot.repositories.base import BaseRepository
class TokenRepository(BaseRepository[TokenBalance]):
    def __init__(self, session: AsyncSession):
        super().__init__(TokenBalance, session)
    async def get_balance(self, user_id: int) -> TokenBalance:
        result = await self.session.execute(
            select(TokenBalance).where(TokenBalance.user_id == user_id)
        )
        balance = result.scalar_one_or_none()
        if not balance:
            balance = TokenBalance(
                user_id=user_id,
                balance=Decimal("0.00"),
                total_deposited=Decimal("0.00"),
                total_spent=Decimal("0.00"),
                total_transferred_out=Decimal("0.00"),
                total_transferred_in=Decimal("0.00"),
            )
            self.session.add(balance)
            await self.session.commit()
            await self.session.refresh(balance)
        return balance
    async def deposit(
        self,
        user_id: int,
        amount: Decimal,
        payment_method: PaymentMethod,
        description: str = None,
        external_payment_id: str = None,
    ) -> TokenTransaction:
        balance = await self.get_balance(user_id)
        balance_before = balance.balance
        balance.balance += amount
        balance.total_deposited += amount
        transaction = TokenTransaction(
            user_id=user_id,
            type=TransactionType.DEPOSIT,
            amount=amount,
            balance_before=balance_before,
            balance_after=balance.balance,
            description=description or f"Пополнение баланса",
            payment_method=payment_method,
            external_payment_id=external_payment_id,
            status=TransactionStatus.COMPLETED,
        )
        self.session.add(transaction)
        await self.session.commit()
        await self.session.refresh(transaction)
        return transaction
    async def spend(
        self,
        user_id: int,
        amount: Decimal,
        route_id: int = None,
        description: str = None,
    ) -> Tuple[bool, Optional[TokenTransaction], str]:
        balance = await self.get_balance(user_id)
        if balance.balance < amount:
            return False, None, "Недостаточно токенов на балансе"
        balance_before = balance.balance
        balance.balance -= amount
        balance.total_spent += amount
        transaction = TokenTransaction(
            user_id=user_id,
            type=TransactionType.PURCHASE,
            amount=amount,
            balance_before=balance_before,
            balance_after=balance.balance,
            description=description or "Покупка экскурсии",
            related_route_id=route_id,
            payment_method=PaymentMethod.TRANSFER,
            status=TransactionStatus.COMPLETED,
        )
        self.session.add(transaction)
        await self.session.commit()
        await self.session.refresh(transaction)
        return True, transaction, "Успешно"
    async def transfer(
        self,
        from_user_id: int,
        to_user_id: int,
        amount: Decimal,
    ) -> Tuple[bool, Optional[TokenTransaction], Optional[TokenTransaction], str]:
        if from_user_id == to_user_id:
            return False, None, None, "Нельзя перевести токены самому себе"
        from_balance = await self.get_balance(from_user_id)
        if from_balance.balance < amount:
            return False, None, None, "Недостаточно токенов на балансе"
        to_balance = await self.get_balance(to_user_id)
        to_user = await self.session.get(User, to_user_id)
        from_user = await self.session.get(User, from_user_id)
        to_name = to_user.username or to_user.first_name or f"ID:{to_user_id}"
        from_name = from_user.username or from_user.first_name or f"ID:{from_user_id}"
        from_balance_before = from_balance.balance
        from_balance.balance -= amount
        from_balance.total_transferred_out += amount
        out_transaction = TokenTransaction(
            user_id=from_user_id,
            type=TransactionType.TRANSFER_OUT,
            amount=amount,
            balance_before=from_balance_before,
            balance_after=from_balance.balance,
            description=f"Перевод пользователю @{to_name}",
            related_user_id=to_user_id,
            payment_method=PaymentMethod.TRANSFER,
            status=TransactionStatus.COMPLETED,
        )
        self.session.add(out_transaction)
        to_balance_before = to_balance.balance
        to_balance.balance += amount
        to_balance.total_transferred_in += amount
        in_transaction = TokenTransaction(
            user_id=to_user_id,
            type=TransactionType.TRANSFER_IN,
            amount=amount,
            balance_before=to_balance_before,
            balance_after=to_balance.balance,
            description=f"Перевод от @{from_name}",
            related_user_id=from_user_id,
            payment_method=PaymentMethod.TRANSFER,
            status=TransactionStatus.COMPLETED,
        )
        self.session.add(in_transaction)
        await self.session.commit()
        await self.session.refresh(out_transaction)
        await self.session.refresh(in_transaction)
        return True, out_transaction, in_transaction, "Перевод выполнен успешно"
    async def get_transactions(
        self,
        user_id: int,
        limit: int = 20,
        offset: int = 0,
        transaction_type: TransactionType = None,
    ) -> List[TokenTransaction]:
        query = select(TokenTransaction).where(
            TokenTransaction.user_id == user_id
        )
        if transaction_type:
            query = query.where(TokenTransaction.type == transaction_type)
        query = query.order_by(TokenTransaction.created_at.desc())
        query = query.offset(offset).limit(limit)
        result = await self.session.execute(query)
        return list(result.scalars().all())
    async def find_user_by_username(self, username: str) -> Optional[User]:
        username = username.lstrip("@").strip()
        result = await self.session.execute(
            select(User).where(User.username.ilike(username))
        )
        return result.scalar_one_or_none()
    async def get_search_limit(self, user_id: int) -> UserSearchLimit:
        result = await self.session.execute(
            select(UserSearchLimit).where(UserSearchLimit.user_id == user_id)
        )
        limit = result.scalar_one_or_none()
        if not limit:
            limit = UserSearchLimit(user_id=user_id)
            self.session.add(limit)
            await self.session.commit()
            await self.session.refresh(limit)
        return limit
    async def check_search_limit(self, user_id: int) -> Tuple[bool, int]:
        limit = await self.get_search_limit(user_id)
        now = datetime.utcnow()
        if limit.blocked_until and now < limit.blocked_until:
            remaining = int((limit.blocked_until - now).total_seconds())
            return False, remaining
        if limit.blocked_until and now >= limit.blocked_until:
            limit.blocked_until = None
            limit.search_count = 0
            limit.first_search_at = None
            await self.session.commit()
        if limit.first_search_at:
            window_end = limit.first_search_at + timedelta(minutes=UserSearchLimit.WINDOW_DURATION_MINUTES)
            if now > window_end:
                limit.search_count = 0
                limit.first_search_at = None
                await self.session.commit()
        return True, 0
    async def record_search(self, user_id: int) -> Tuple[bool, int]:
        can_search, remaining = await self.check_search_limit(user_id)
        if not can_search:
            return False, remaining
        limit = await self.get_search_limit(user_id)
        now = datetime.utcnow()
        if limit.first_search_at is None:
            limit.first_search_at = now
        limit.search_count += 1
        if limit.search_count >= UserSearchLimit.MAX_SEARCHES:
            limit.blocked_until = now + timedelta(minutes=UserSearchLimit.BLOCK_DURATION_MINUTES)
            await self.session.commit()
            return False, UserSearchLimit.BLOCK_DURATION_MINUTES * 60
        await self.session.commit()
        return True, 0
    async def create_deposit(
        self,
        user_id: int,
        amount: Decimal,
        payment_amount: Decimal,
        payment_method: PaymentMethod,
    ) -> TokenDeposit:
        deposit = TokenDeposit(
            user_id=user_id,
            amount=amount,
            payment_amount=payment_amount,
            payment_method=payment_method,
            status=DepositStatus.PENDING,
        )
        self.session.add(deposit)
        await self.session.commit()
        await self.session.refresh(deposit)
        return deposit
    async def complete_deposit(self, deposit_id: int, payment_id: str = None) -> Optional[TokenDeposit]:
        deposit = await self.session.get(TokenDeposit, deposit_id)
        if not deposit or deposit.status != DepositStatus.PENDING:
            return None
        deposit.status = DepositStatus.COMPLETED
        deposit.payment_id = payment_id
        deposit.completed_at = datetime.utcnow()
        await self.deposit(
            user_id=deposit.user_id,
            amount=deposit.amount,
            payment_method=deposit.payment_method,
            external_payment_id=payment_id,
        )
        await self.session.commit()
        await self.session.refresh(deposit)
        return deposit
    async def get_pending_deposit(self, user_id: int, payment_method: PaymentMethod) -> Optional[TokenDeposit]:
        result = await self.session.execute(
            select(TokenDeposit).where(
                and_(
                    TokenDeposit.user_id == user_id,
                    TokenDeposit.payment_method == payment_method,
                    TokenDeposit.status == DepositStatus.PENDING,
                )
            ).order_by(TokenDeposit.created_at.desc())
        )
<<<<<<< HEAD
        return result.scalar_one_or_none()
    async def add_referral_reward(
        self,
        referrer_user_id: int,
        amount: "Decimal",
        referred_user_id: int,
        route_id: int,
        description: str = "Реферальное вознаграждение",
    ) -> TokenTransaction:
        balance = await self.get_balance(referrer_user_id)
        balance_before = balance.balance
        balance.balance += amount
        balance.total_deposited += amount
        transaction = TokenTransaction(
            user_id=referrer_user_id,
            type=TransactionType.REFERRAL_REWARD,
            amount=amount,
            balance_before=balance_before,
            balance_after=balance.balance,
            description=description,
            related_user_id=referred_user_id,
            related_route_id=route_id,
            payment_method=PaymentMethod.SYSTEM,
            status=TransactionStatus.COMPLETED,
        )
        self.session.add(transaction)
        await self.session.commit()
        await self.session.refresh(transaction)
        return transaction
    async def get_referral_earned_total(self, user_id: int) -> Decimal:
        result = await self.session.execute(
            select(func.coalesce(func.sum(TokenTransaction.amount), 0)).where(
                and_(
                    TokenTransaction.user_id == user_id,
                    TokenTransaction.type == TransactionType.REFERRAL_REWARD,
                )
            )
        )
        return result.scalar() or Decimal("0")
=======
        return result.scalar_one_or_none()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
