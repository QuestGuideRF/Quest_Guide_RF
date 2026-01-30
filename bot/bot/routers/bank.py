import logging
from decimal import Decimal
from datetime import datetime
from aiogram import Router, F
from aiogram.filters import Command
from aiogram.fsm.context import FSMContext
from aiogram.types import Message, CallbackQuery
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from bot.models.token_transaction import TransactionType, PaymentMethod
from bot.repositories.token import TokenRepository
from bot.repositories.city import CityRepository
from bot.repositories.route import RouteRepository
from bot.repositories.payment import PaymentRepository
from bot.keyboards.bank import BankKeyboards
from bot.fsm.states import BankStates
from bot.utils.i18n import i18n, get_localized_field
from bot.loader import bot
router = Router()
logger = logging.getLogger(__name__)
@router.message(Command("token"))
async def cmd_token(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.clear()
    await show_bank_menu(message, session, user)
@router.callback_query(F.data == "open_bank")
async def cb_open_bank(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.clear()
    await show_bank_menu(callback.message, session, user, edit=True)
    await callback.answer()
@router.callback_query(F.data == "bank:menu")
async def cb_bank_menu(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.clear()
    await show_bank_menu(callback.message, session, user, edit=True)
    await callback.answer()
async def show_bank_menu(
    message: Message,
    session: AsyncSession,
    user: User,
    edit: bool = False,
):
    token_repo = TokenRepository(session)
    balance = await token_repo.get_balance(user.id)
    text = f"{i18n.get('bank_menu_title', user.language)}\n\n"
    text += f"{i18n.get('bank_balance', user.language)}: <b>{balance.balance:.0f}₽</b>\n\n"
    text += f"{i18n.get('bank_total_deposited', user.language)}: {balance.total_deposited:.0f}₽\n"
    text += f"{i18n.get('bank_total_spent', user.language)}: {balance.total_spent:.0f}₽\n"
    text += f"{i18n.get('bank_total_transferred', user.language)}: {balance.total_transferred_out:.0f}₽\n"
    text += f"{i18n.get('bank_total_received', user.language)}: {balance.total_transferred_in:.0f}₽\n"
    keyboard = BankKeyboards.main_menu(user.language)
    if edit:
        try:
            await message.edit_text(text, reply_markup=keyboard, parse_mode="HTML")
        except:
            await message.answer(text, reply_markup=keyboard, parse_mode="HTML")
    else:
        await message.answer(text, reply_markup=keyboard, parse_mode="HTML")
@router.callback_query(F.data == "bank:deposit")
async def cb_deposit_methods(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.clear()
    text = i18n.get("bank_deposit_title", user.language)
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.deposit_methods(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("bank:deposit:yookassa"))
async def cb_deposit_yookassa(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data_parts = callback.data.split(":")
    if len(data_parts) == 3:
        text = i18n.get("bank_deposit_amount_title", user.language)
        await callback.message.edit_text(
            text,
            reply_markup=BankKeyboards.deposit_amounts("yookassa", user.language),
            parse_mode="HTML"
        )
    elif len(data_parts) == 4:
        amount_str = data_parts[3]
        if amount_str == "custom":
            await state.set_state(BankStates.waiting_deposit_amount)
            await state.update_data(payment_method="yookassa")
            await callback.message.edit_text(
                i18n.get("bank_enter_amount", user.language),
                reply_markup=BankKeyboards.back_to_bank(user.language),
                parse_mode="HTML"
            )
        else:
            amount = int(amount_str)
            await show_deposit_confirm(callback.message, user, amount, "yookassa")
    await callback.answer()
@router.callback_query(F.data.startswith("bank:deposit:stars"))
async def cb_deposit_stars(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    data_parts = callback.data.split(":")
    if len(data_parts) == 3:
        text = i18n.get("bank_deposit_amount_title", user.language)
        await callback.message.edit_text(
            text,
            reply_markup=BankKeyboards.deposit_amounts("stars", user.language),
            parse_mode="HTML"
        )
    elif len(data_parts) == 4:
        amount_str = data_parts[3]
        if amount_str == "custom":
            await state.set_state(BankStates.waiting_deposit_amount)
            await state.update_data(payment_method="stars")
            await callback.message.edit_text(
                i18n.get("bank_enter_amount", user.language),
                reply_markup=BankKeyboards.back_to_bank(user.language),
                parse_mode="HTML"
            )
        else:
            amount = int(amount_str)
            await show_deposit_confirm(callback.message, user, amount, "stars")
    await callback.answer()
@router.message(BankStates.waiting_deposit_amount)
async def process_custom_deposit_amount(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    try:
        amount = int(message.text.strip())
        if amount < 50 or amount > 100000:
            await message.answer(
                i18n.get("bank_invalid_amount", user.language),
                reply_markup=BankKeyboards.back_to_bank(user.language)
            )
            return
    except ValueError:
        await message.answer(
            i18n.get("bank_invalid_amount", user.language),
            reply_markup=BankKeyboards.back_to_bank(user.language)
        )
        return
    data = await state.get_data()
    payment_method = data.get("payment_method", "yookassa")
    await state.clear()
    await show_deposit_confirm(message, user, amount, payment_method)
async def show_deposit_confirm(
    message: Message,
    user: User,
    amount: int,
    payment_method: str,
):
    method_name = i18n.get("bank_yookassa", user.language) if payment_method == "yookassa" else i18n.get("bank_telegram_stars", user.language)
    text = i18n.get("bank_deposit_confirm", user.language).format(
        amount=amount,
        method=method_name
    )
    await message.answer(
        text,
        reply_markup=BankKeyboards.confirm_deposit(amount, payment_method, user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("bank:confirm_deposit:"))
async def cb_confirm_deposit(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    parts = callback.data.split(":")
    payment_method = parts[2]
    amount = int(parts[3])
    token_repo = TokenRepository(session)
    if payment_method == "yookassa":
        from bot.config import load_config
        from bot.services.payments import PaymentService
        config = load_config()
        payment_service = PaymentService(config.payment)
        if not config.payment.provider_token:
            await token_repo.deposit(
                user_id=user.id,
                amount=Decimal(str(amount)),
                payment_method=PaymentMethod.YOOKASSA,
                description=i18n.get("bank_deposit_test", user.language)
            )
            await callback.message.edit_text(
                i18n.get("bank_deposit_success", user.language).format(amount=amount),
                parse_mode="HTML"
            )
            await callback.answer()
            return
        deposit = await token_repo.create_deposit(
            user_id=user.id,
            amount=Decimal(str(amount)),
            payment_amount=Decimal(str(amount)),
            payment_method=PaymentMethod.YOOKASSA,
        )
        payload = f"token_deposit:{deposit.id}:user:{user.telegram_id}"
        invoice_params = payment_service.create_invoice(
            route_name=i18n.get("bank_deposit_invoice_title", user.language),
            route_description=i18n.get("bank_deposit_invoice_desc", user.language, amount=amount),
            price=amount,
            payload=payload,
        )
        try:
            await bot.send_invoice(
                chat_id=callback.message.chat.id,
                **invoice_params,
            )
            await callback.answer()
        except Exception as e:
            logger.error(f"Error creating invoice: {e}")
            await callback.answer(i18n.get("payment_error", user.language, error=str(e)), show_alert=True)
    elif payment_method == "stars":
        from aiogram.types import LabeledPrice
        stars_amount = amount
        deposit = await token_repo.create_deposit(
            user_id=user.id,
            amount=Decimal(str(amount)),
            payment_amount=Decimal(str(stars_amount)),
            payment_method=PaymentMethod.TELEGRAM_STARS,
        )
        try:
            await bot.send_invoice(
                chat_id=callback.message.chat.id,
                title=i18n.get("bank_deposit_invoice_title", user.language),
                description=i18n.get("bank_deposit_invoice_desc", user.language, amount=amount),
                payload=f"token_deposit_stars:{deposit.id}:user:{user.telegram_id}",
                currency="XTR",
                prices=[LabeledPrice(label=i18n.get("bank_tokens_label", user.language), amount=stars_amount)],
                provider_token="",
            )
            await callback.answer()
        except Exception as e:
            logger.error(f"Error creating Stars invoice: {e}")
            await callback.answer(i18n.get("payment_error", user.language, error=str(e)), show_alert=True)
@router.callback_query(F.data == "bank:transfer")
async def cb_transfer_start(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    await state.set_state(BankStates.waiting_transfer_username)
    text = i18n.get("bank_transfer_title", user.language) + "\n\n"
    text += i18n.get("bank_transfer_instruction", user.language)
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.cancel_transfer(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.message(BankStates.waiting_transfer_username)
async def process_transfer_username(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    token_repo = TokenRepository(session)
    can_search, remaining_seconds = await token_repo.check_search_limit(user.id)
    if not can_search:
        minutes = remaining_seconds // 60
        seconds = remaining_seconds % 60
        await message.answer(
            i18n.get("bank_search_blocked", user.language).format(
                minutes=minutes,
                seconds=seconds
            ),
            reply_markup=BankKeyboards.back_to_bank(user.language)
        )
        await state.clear()
        return
    can_continue, block_time = await token_repo.record_search(user.id)
    username = message.text.strip().lstrip("@")
    recipient = await token_repo.find_user_by_username(username)
    if not recipient:
        await message.answer(
            i18n.get("bank_user_not_found", user.language),
            reply_markup=BankKeyboards.cancel_transfer(user.language)
        )
        return
    if recipient.id == user.id:
        await message.answer(
            i18n.get("bank_cannot_transfer_self", user.language),
            reply_markup=BankKeyboards.cancel_transfer(user.language)
        )
        return
    await state.update_data(
        recipient_id=recipient.id,
        recipient_username=recipient.username or recipient.first_name or f"ID:{recipient.id}"
    )
    await state.set_state(BankStates.waiting_transfer_amount)
    await message.answer(
        i18n.get("bank_enter_transfer_amount", user.language),
        reply_markup=BankKeyboards.cancel_transfer(user.language)
    )
@router.message(BankStates.waiting_transfer_amount)
async def process_transfer_amount(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    try:
        amount = Decimal(message.text.strip())
        if amount <= 0 or amount > 1000000:
            await message.answer(
                i18n.get("bank_invalid_amount", user.language),
                reply_markup=BankKeyboards.cancel_transfer(user.language)
            )
            return
    except:
        await message.answer(
            i18n.get("bank_invalid_amount", user.language),
            reply_markup=BankKeyboards.cancel_transfer(user.language)
        )
        return
    token_repo = TokenRepository(session)
    balance = await token_repo.get_balance(user.id)
    if balance.balance < amount:
        await message.answer(
            i18n.get("bank_insufficient_balance", user.language).format(
                balance=f"{balance.balance:.0f}"
            ),
            reply_markup=BankKeyboards.back_to_bank(user.language)
        )
        await state.clear()
        return
    data = await state.get_data()
    recipient_id = data.get("recipient_id")
    recipient_username = data.get("recipient_username")
    balance_after = balance.balance - amount
    text = i18n.get("bank_transfer_confirm", user.language).format(
        username=recipient_username,
        amount=f"{amount:.0f}",
        balance_after=f"{balance_after:.0f}"
    )
    await state.update_data(amount=str(amount))
    await state.set_state(BankStates.confirming_transfer)
    await message.answer(
        text,
        reply_markup=BankKeyboards.transfer_confirm(recipient_id, amount, user.language),
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("bank:confirm_transfer:"))
async def cb_confirm_transfer(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    parts = callback.data.split(":")
    recipient_id = int(parts[2])
    amount = Decimal(parts[3])
    token_repo = TokenRepository(session)
    success, out_tx, in_tx, error_msg = await token_repo.transfer(
        from_user_id=user.id,
        to_user_id=recipient_id,
        amount=amount
    )
    if not success:
        await callback.message.edit_text(
            f"❌ {error_msg}",
            reply_markup=BankKeyboards.back_to_bank(user.language)
        )
        await callback.answer()
        await state.clear()
        return
    data = await state.get_data()
    recipient_username = data.get("recipient_username", "")
    await callback.message.edit_text(
        i18n.get("bank_transfer_success", user.language).format(
            amount=f"{amount:.0f}",
            username=recipient_username
        ),
        reply_markup=BankKeyboards.back_to_bank(user.language),
        parse_mode="HTML"
    )
    from bot.repositories.user import UserRepository
    user_repo = UserRepository(session)
    recipient = await user_repo.get(recipient_id)
    if recipient:
        sender_name = user.username or user.first_name or f"ID:{user.id}"
        try:
            await bot.send_message(
                chat_id=recipient.telegram_id,
                text=i18n.get("bank_transfer_received", recipient.language).format(
                    amount=f"{amount:.0f}",
                    username=sender_name
                ),
                parse_mode="HTML"
            )
        except Exception as e:
            logger.warning(f"Could not notify recipient {recipient_id}: {e}")
    await callback.answer()
    await state.clear()
@router.callback_query(F.data == "bank:buy_tour")
async def cb_buy_tour(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    city_repo = CityRepository(session)
    cities = await city_repo.get_active()
    if not cities:
        await callback.answer(i18n.get("no_cities", user.language), show_alert=True)
        return
    text = i18n.get("bank_buy_city_title", user.language)
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.city_list_for_purchase(cities, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("bank:city:"))
async def cb_select_city_for_purchase(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    city_id = int(callback.data.split(":")[2])
    route_repo = RouteRepository(session)
    token_repo = TokenRepository(session)
    payment_repo = PaymentRepository(session)
    routes = await route_repo.get_by_city(city_id)
    balance = await token_repo.get_balance(user.id)
    active_routes = [r for r in routes if r.is_active]
    if not active_routes:
        await callback.answer(i18n.get("no_routes", user.language), show_alert=True)
        return
    paid_route_ids = set()
    for route in active_routes:
        if await payment_repo.has_paid_for_route(user.id, route.id):
            paid_route_ids.add(route.id)
    text = i18n.get("bank_buy_route_title", user.language).format(
        balance=f"{balance.balance:.0f}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.route_list_for_purchase(
            active_routes, city_id, balance.balance, user.language, paid_route_ids=paid_route_ids
        ),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("bank:route:"))
async def cb_select_route_for_purchase(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    route_repo = RouteRepository(session)
    token_repo = TokenRepository(session)
    payment_repo = PaymentRepository(session)
    route = await route_repo.get(route_id)
    if not route:
        await callback.answer(i18n.get("route_not_found", user.language), show_alert=True)
        return
    has_paid = await payment_repo.has_paid_for_route(user.id, route_id)
    if has_paid:
        await callback.answer(i18n.get("bank_already_purchased", user.language), show_alert=True)
        return
    balance = await token_repo.get_balance(user.id)
    if balance.balance < route.price:
        await callback.answer(
            i18n.get("bank_insufficient_balance", user.language).format(
                balance=f"{balance.balance:.0f}"
            ),
            show_alert=True
        )
        return
    route_name = get_localized_field(route, 'name', user.language)
    balance_after = balance.balance - route.price
    text = i18n.get("bank_purchase_confirm", user.language).format(
        route_name=route_name,
        price=route.price,
        balance_after=f"{balance_after:.0f}"
    )
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.confirm_purchase(route_id, route.price, user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("bank:confirm_purchase:"))
async def cb_confirm_purchase(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    route_id = int(callback.data.split(":")[2])
    route_repo = RouteRepository(session)
    token_repo = TokenRepository(session)
    payment_repo = PaymentRepository(session)
    route = await route_repo.get(route_id)
    if not route:
        await callback.answer(i18n.get("route_not_found", user.language), show_alert=True)
        return
    has_paid = await payment_repo.has_paid_for_route(user.id, route_id)
    if has_paid:
        await callback.answer(i18n.get("bank_already_purchased", user.language), show_alert=True)
        return
    success, transaction, error = await token_repo.spend(
        user_id=user.id,
        amount=Decimal(str(route.price)),
        route_id=route_id,
        description=i18n.get("bank_tx_purchase", user.language) + ": " + (get_localized_field(route, "name", user.language) or route.name)
    )
    if not success:
        await callback.answer(error, show_alert=True)
        return
    await payment_repo.create_payment(
        user_id=user.id,
        route_id=route_id,
        amount=route.price,
    )
    payments = await payment_repo.get_all()
    payment = next(
        (p for p in payments if p.user_id == user.id and p.route_id == route_id and p.status.value == "pending"),
        None
    )
    if payment:
        await payment_repo.mark_success(payment.id, "token_purchase", f"tx_{transaction.id}")
    route_name = get_localized_field(route, 'name', user.language)
    await callback.message.edit_text(
        i18n.get("bank_purchase_success", user.language).format(route_name=route_name),
        reply_markup=BankKeyboards.back_to_bank(user.language),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "bank:history")
async def cb_history(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    token_repo = TokenRepository(session)
    transactions = await token_repo.get_transactions(user.id, limit=10)
    if not transactions:
        text = i18n.get("bank_history_empty", user.language)
    else:
        text = i18n.get("bank_history_title", user.language).format(count=len(transactions)) + "\n\n"
        type_labels = {
            TransactionType.DEPOSIT: "bank_tx_deposit",
            TransactionType.PURCHASE: "bank_tx_purchase",
            TransactionType.TRANSFER_OUT: "bank_tx_transfer_out",
            TransactionType.TRANSFER_IN: "bank_tx_transfer_in",
            TransactionType.REFUND: "bank_tx_refund",
        }
        for tx in transactions:
            type_label = i18n.get(type_labels.get(tx.type, "bank_tx_deposit"), user.language)
            sign = "+" if tx.type in [TransactionType.DEPOSIT, TransactionType.TRANSFER_IN, TransactionType.REFUND] else "-"
            date_str = tx.created_at.strftime("%d.%m.%Y %H:%M")
            text += f"{type_label}: {sign}{tx.amount:.0f}₽\n"
            text += f"<i>{date_str}</i>\n\n"
    await callback.message.edit_text(
        text,
        reply_markup=BankKeyboards.back_to_bank(user.language),
        parse_mode="HTML"
    )
    await callback.answer()