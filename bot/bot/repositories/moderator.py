from datetime import datetime
from decimal import Decimal
from typing import Optional, List
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User, UserRole
from bot.models.moderator_request import ModeratorRequest, RequestStatus
from bot.models.moderator_balance import (
    ModeratorBalance,
    ModeratorTransaction,
    ModeratorTransactionType,
    WithdrawalRequest,
    WithdrawalStatus,
)
class ModeratorRepository:
    def __init__(self, session: AsyncSession):
        self.session = session
    async def create_request(self, user_id: int, message: str) -> ModeratorRequest:
        request = ModeratorRequest(
            user_id=user_id,
            message=message,
            status=RequestStatus.PENDING,
        )
        self.session.add(request)
        await self.session.commit()
        await self.session.refresh(request)
        return request
    async def get_pending_request(self, user_id: int) -> Optional[ModeratorRequest]:
        result = await self.session.execute(
            select(ModeratorRequest)
            .where(
                ModeratorRequest.user_id == user_id,
                ModeratorRequest.status == RequestStatus.PENDING
            )
            .order_by(ModeratorRequest.created_at.desc())
            .limit(1)
        )
        return result.scalar_one_or_none()
    async def get_user_requests(self, user_id: int) -> List[ModeratorRequest]:
        result = await self.session.execute(
            select(ModeratorRequest)
            .where(ModeratorRequest.user_id == user_id)
            .order_by(ModeratorRequest.created_at.desc())
        )
        return list(result.scalars().all())
    async def get_all_pending_requests(self) -> List[ModeratorRequest]:
        result = await self.session.execute(
            select(ModeratorRequest)
            .where(ModeratorRequest.status == RequestStatus.PENDING)
            .order_by(ModeratorRequest.created_at.asc())
        )
        return list(result.scalars().all())
    async def approve_request(
        self,
        request_id: int,
        admin_id: int,
        comment: Optional[str] = None
    ) -> Optional[ModeratorRequest]:
        request = await self.session.get(ModeratorRequest, request_id)
        if not request or request.status != RequestStatus.PENDING:
            return None
        request.status = RequestStatus.APPROVED
        request.reviewed_by = admin_id
        request.reviewed_at = datetime.utcnow()
        if comment:
            request.admin_comment = comment
        user = await self.session.get(User, request.user_id)
        if user:
            user.role = UserRole.MODERATOR
        balance = await self.get_or_create_balance(request.user_id)
        await self.session.commit()
        await self.session.refresh(request)
        return request
    async def reject_request(
        self,
        request_id: int,
        admin_id: int,
        comment: Optional[str] = None
    ) -> Optional[ModeratorRequest]:
        request = await self.session.get(ModeratorRequest, request_id)
        if not request or request.status != RequestStatus.PENDING:
            return None
        request.status = RequestStatus.REJECTED
        request.reviewed_by = admin_id
        request.reviewed_at = datetime.utcnow()
        if comment:
            request.admin_comment = comment
        await self.session.commit()
        await self.session.refresh(request)
        return request
    async def get_or_create_balance(self, user_id: int) -> ModeratorBalance:
        result = await self.session.execute(
            select(ModeratorBalance).where(ModeratorBalance.user_id == user_id)
        )
        balance = result.scalar_one_or_none()
        if not balance:
            balance = ModeratorBalance(user_id=user_id)
            self.session.add(balance)
            await self.session.commit()
            await self.session.refresh(balance)
        return balance
    async def add_earning(
        self,
        moderator_id: int,
        amount: Decimal,
        route_id: int,
        buyer_id: int,
        description: Optional[str] = None
    ) -> ModeratorTransaction:
        balance = await self.get_or_create_balance(moderator_id)
        balance.balance += amount
        balance.total_earned += amount
        transaction = ModeratorTransaction(
            user_id=moderator_id,
            type=ModeratorTransactionType.EARNING,
            amount=amount,
            route_id=route_id,
            buyer_user_id=buyer_id,
            description=description or f"Доход от продажи маршрута"
        )
        self.session.add(transaction)
        await self.session.commit()
        await self.session.refresh(transaction)
        return transaction
    async def get_moderator_stats(self, user_id: int) -> dict:
        from sqlalchemy import func, text
        balance = await self.get_or_create_balance(user_id)
        routes_result = await self.session.execute(
            text("SELECT COUNT(*) FROM routes WHERE creator_id = :user_id"),
            {"user_id": user_id}
        )
        total_routes = routes_result.scalar() or 0
        sales_result = await self.session.execute(
            select(func.count(ModeratorTransaction.id))
            .where(
                ModeratorTransaction.user_id == user_id,
                ModeratorTransaction.type == ModeratorTransactionType.EARNING
            )
        )
        total_sales = sales_result.scalar() or 0
        return {
            "balance": balance.balance,
            "total_earned": balance.total_earned,
            "total_withdrawn": balance.total_withdrawn,
            "total_routes": total_routes,
            "total_sales": total_sales,
        }
    async def get_transactions(
        self,
        user_id: int,
        limit: int = 20
    ) -> List[ModeratorTransaction]:
        result = await self.session.execute(
            select(ModeratorTransaction)
            .where(ModeratorTransaction.user_id == user_id)
            .order_by(ModeratorTransaction.created_at.desc())
            .limit(limit)
        )
        return list(result.scalars().all())
    async def create_withdrawal_request(
        self,
        user_id: int,
        amount: Decimal,
        payment_details: str
    ) -> Optional[WithdrawalRequest]:
        balance = await self.get_or_create_balance(user_id)
        if balance.balance < amount:
            return None
        request = WithdrawalRequest(
            user_id=user_id,
            amount=amount,
            payment_details=payment_details,
            status=WithdrawalStatus.PENDING,
        )
        self.session.add(request)
        await self.session.commit()
        await self.session.refresh(request)
        return request
    async def process_withdrawal(
        self,
        request_id: int,
        admin_id: int,
        approve: bool,
        comment: Optional[str] = None
    ) -> Optional[WithdrawalRequest]:
        request = await self.session.get(WithdrawalRequest, request_id)
        if not request or request.status != WithdrawalStatus.PENDING:
            return None
        if approve:
            balance = await self.get_or_create_balance(request.user_id)
            if balance.balance < request.amount:
                return None
            balance.balance -= request.amount
            balance.total_withdrawn += request.amount
            transaction = ModeratorTransaction(
                user_id=request.user_id,
                type=ModeratorTransactionType.WITHDRAWAL,
                amount=request.amount,
                description=f"Вывод средств"
            )
            self.session.add(transaction)
            request.status = WithdrawalStatus.COMPLETED
        else:
            request.status = WithdrawalStatus.REJECTED
        request.processed_by = admin_id
        request.processed_at = datetime.utcnow()
        if comment:
            request.admin_comment = comment
        await self.session.commit()
        await self.session.refresh(request)
        return request