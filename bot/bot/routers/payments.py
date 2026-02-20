<<<<<<< HEAD
import logging
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from aiogram import Router, F
from aiogram.fsm.context import FSMContext
from aiogram.types import Message, CallbackQuery, PreCheckoutQuery, ContentType
from sqlalchemy.ext.asyncio import AsyncSession
<<<<<<< HEAD
from bot.loader import bot, config
=======
from sqlalchemy import text
from bot.config import load_config
from bot.loader import bot
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from bot.models.user import User
from bot.repositories.route import RouteRepository
from bot.repositories.payment import PaymentRepository
from bot.repositories.promo_code import PromoCodeRepository
from bot.services.payments import PaymentService
from bot.keyboards.user import UserKeyboards
from bot.utils.helpers import format_duration, format_distance
from bot.fsm.states import UserStates
<<<<<<< HEAD
logger = logging.getLogger(__name__)
router = Router()
=======
router = Router()
config = load_config()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
payment_service = PaymentService(config.payment)
@router.callback_query(F.data.startswith("promo_code:"))
async def enter_promo_code(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    route_id = int(callback.data.split(":")[1])
    await state.update_data(route_id=route_id, route_message_id=callback.message.message_id)
    await state.set_state(UserStates.waiting_promo_code)
    await callback.message.answer(
        i18n.get("promo_code_prompt", user.language),
        reply_markup=UserKeyboards.route_detail(route_id, False, user.language, False)
    )
    await callback.answer()
@router.message(UserStates.waiting_promo_code)
async def process_promo_code(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n, get_localized_field
    from bot.repositories.route import RouteRepository
<<<<<<< HEAD
    if not message.text:
        await message.answer(i18n.get("promo_code_enter_command", user.language))
        return
    if message.text.strip().lower() in ['отмена', 'cancel', 'назад', 'back', '❌ отмена']:
=======
    if message.text and message.text.strip().lower() in ['отмена', 'cancel', 'назад', 'back', '❌ отмена']:
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        await state.clear()
        await message.answer(i18n.get("cancelled", user.language, default="❌ Отменено"))
        return
    promo_code_text = message.text.strip().upper()
    data = await state.get_data()
    route_id = data.get("route_id")
    promo_repo = PromoCodeRepository(session)
    if not route_id:
        from bot.repositories.progress import ProgressRepository
        progress_repo = ProgressRepository(session)
<<<<<<< HEAD
        progress = await progress_repo.get_active_or_paused_progress(user.id)
        if progress:
            route_id = progress.route_id
=======
        result = await session.execute(
            text(),
            {"user_id": user.id}
        )
        progress_row = result.fetchone()
        if progress_row:
            route_id = progress_row[0]
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    if route_id:
        route_repo = RouteRepository(session)
        route = await route_repo.get(route_id)
        if not route:
            await message.answer(i18n.get("route_not_found", user.language))
            await state.clear()
            return
<<<<<<< HEAD
=======
        import logging
        logger = logging.getLogger(__name__)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        logger.info(f"Валидация промокода {promo_code_text} для пользователя {user.id} (telegram_id={user.telegram_id}), route_id={route_id}")
        is_valid, error_msg, promo_code = await promo_repo.validate_promo_code(
            promo_code_text, user.id, route_id
        )
        logger.info(f"Результат валидации: is_valid={is_valid}, error_msg={error_msg}")
        if not is_valid:
            if error_msg and error_msg.startswith("promo_code_"):
                error_text = i18n.get(error_msg, user.language, default=error_msg)
                logger.info(f"Отправка ошибки пользователю: {error_text}")
                await message.answer(error_text)
            else:
                error_text = error_msg or i18n.get("promo_code_invalid", user.language)
                logger.info(f"Отправка ошибки пользователю: {error_text}")
                await message.answer(error_text)
            return
<<<<<<< HEAD
        discount_type_value = promo_code.discount_type.value.lower() if hasattr(promo_code.discount_type, 'value') else str(promo_code.discount_type).lower()
        if discount_type_value == "free_route":
            await promo_repo.apply_promo_code(promo_code, user.id, route_id, route.price)
            await state.clear()
            route_with_points = await route_repo.get_with_points(route_id)
            route_name = get_localized_field(route_with_points, 'name', user.language)
            route_description = get_localized_field(route_with_points, 'description', user.language)
            description = f"📍 <b>{route_name}</b>\n\n"
            if route_description:
                description += f"{route_description}\n\n"
            description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
            if route_with_points.points:
                description += f"• {i18n.get('points', user.language)}: {len(route_with_points.points)}\n"
            if route.estimated_duration:
                description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
            if route.distance:
                description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
            description += f"• {i18n.get('price', user.language)}: {route.price} грошей\n"
            description += i18n.get("route_paid", user.language)
            route_message_id = data.get("route_message_id")
            if route_message_id:
                try:
                    await bot.edit_message_text(
                        chat_id=message.chat.id,
                        message_id=route_message_id,
                        text=description,
                        reply_markup=UserKeyboards.route_detail(route_id, True, user.language),
                        parse_mode="HTML",
                    )
                except Exception as e:
                    logger.warning("payments: не удалось отредактировать сообщение с маршрутом (route_id=%s): %s", route_id, e)
            await message.answer(
                i18n.get("promo_route_activated_free", user.language).format(route_name=route_name),
                reply_markup=UserKeyboards.promo_activated(route_id, user.language),
            )
            return
        final_price, discount_amount = await promo_repo.apply_promo_code(
            promo_code, user.id, route_id, route.price
        )
        if discount_type_value == "percentage":
            discount_text = f"{promo_code.discount_value}%"
        elif discount_type_value == "fixed":
            discount_text = f"{int(discount_amount)} г"
=======
        final_price, discount_amount = await promo_repo.apply_promo_code(
            promo_code, user.id, route_id, route.price
        )
        discount_type_value = promo_code.discount_type.value.lower() if hasattr(promo_code.discount_type, 'value') else str(promo_code.discount_type).lower()
        if discount_type_value == "percentage":
            discount_text = f"{promo_code.discount_value}%"
        elif discount_type_value == "fixed":
            discount_text = f"{int(discount_amount)}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        else:
            discount_text = i18n.get("promo_code_free", user.language)
        await message.answer(
            i18n.get("promo_code_applied", user.language).format(discount=discount_text) + "\n\n" +
            i18n.get("promo_code_price_info", user.language).format(original=route.price, final=final_price)
        )
        await state.update_data(promo_code_id=promo_code.id, final_price=final_price)
        await state.set_state(UserStates.waiting_payment)
        route_with_points = await route_repo.get_with_points(route_id)
        route_name = get_localized_field(route, 'name', user.language)
        route_description = get_localized_field(route, 'description', user.language)
        description = f"📍 <b>{route_name}</b>\n\n"
        if route_description:
            description += f"{route_description}\n\n"
        description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
        if route_with_points and route_with_points.points:
            description += f"• {i18n.get('points', user.language)}: {len(route_with_points.points)}\n"
        if route.estimated_duration:
            description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
        if route.distance:
            description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
<<<<<<< HEAD
        description += f"• {i18n.get('price', user.language)}: <s>{route.price} г</s> <b>{final_price} г</b>\n"
=======
        description += f"• {i18n.get('price', user.language)}: <s>{route.price}₽</s> <b>{final_price}₽</b>\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        route_message_id = data.get("route_message_id")
        if route_message_id:
            try:
                await bot.edit_message_text(
                    chat_id=message.chat.id,
                    message_id=route_message_id,
                    text=description,
                    reply_markup=UserKeyboards.route_detail(route_id, False, user.language, False),
                    parse_mode="HTML",
                )
<<<<<<< HEAD
            except Exception as e:
                logger.warning("payments: не удалось отредактировать сообщение с маршрутом (route_id=%s): %s", route_id, e)
=======
            except:
                pass
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    else:
        promo_code = await promo_repo.get_by_code(promo_code_text)
        if not promo_code:
            await message.answer(i18n.get("promo_code_not_found", user.language))
            await state.clear()
            return
        if not promo_code.is_active:
            await message.answer(i18n.get("promo_code_invalid", user.language))
            await state.clear()
            return
        discount_type_value = promo_code.discount_type.value.lower() if hasattr(promo_code.discount_type, 'value') else str(promo_code.discount_type).lower()
<<<<<<< HEAD
        if discount_type_value == "free_route" and promo_code.route_id:
            route_id_for_promo = promo_code.route_id
            is_valid, error_msg, _ = await promo_repo.validate_promo_code(
                promo_code_text, user.id, route_id_for_promo
            )
            if not is_valid:
                if error_msg and error_msg.startswith("promo_code_"):
                    await message.answer(i18n.get(error_msg, user.language, default=error_msg))
                else:
                    await message.answer(error_msg or i18n.get("promo_code_invalid", user.language))
                await state.clear()
                return
            route_repo = RouteRepository(session)
            route = await route_repo.get(route_id_for_promo)
            if not route:
                await message.answer(i18n.get("route_not_found", user.language))
                await state.clear()
                return
            await promo_repo.apply_promo_code(promo_code, user.id, route_id_for_promo, route.price)
            await state.clear()
            route_with_points = await route_repo.get_with_points(route_id_for_promo)
            route_name = get_localized_field(route_with_points, 'name', user.language)
            route_description = get_localized_field(route_with_points, 'description', user.language)
            description = f"📍 <b>{route_name}</b>\n\n"
            if route_description:
                description += f"{route_description}\n\n"
            description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
            if route_with_points.points:
                description += f"• {i18n.get('points', user.language)}: {len(route_with_points.points)}\n"
            if route.estimated_duration:
                description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
            if route.distance:
                description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
            description += f"• {i18n.get('price', user.language)}: {route.price} грошей\n"
            description += i18n.get("route_paid", user.language)
            await message.answer(
                description,
                reply_markup=UserKeyboards.route_detail(route_id_for_promo, True, user.language),
                parse_mode="HTML",
            )
            await message.answer(
                i18n.get("promo_route_activated_free", user.language).format(route_name=route_name),
                reply_markup=UserKeyboards.promo_activated(route_id_for_promo, user.language),
            )
            return
        discount_text = f"{promo_code.discount_value}%" if discount_type_value == "percentage" else (f"{promo_code.discount_value} г" if discount_type_value == "fixed" else i18n.get("promo_code_free", user.language))
=======
        if discount_type_value == "percentage":
            discount_text = f"{promo_code.discount_value}%"
        elif discount_type_value == "fixed":
            discount_text = f"{promo_code.discount_value}₽"
        else:
            discount_text = i18n.get("promo_code_free", user.language)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        route_info = ""
        if promo_code.route_id:
            route_repo = RouteRepository(session)
            route = await route_repo.get(promo_code.route_id)
            if route:
                route_name = get_localized_field(route, 'name', user.language)
                route_info = f"\n\n📍 {i18n.get('for_route', user.language, default='Для маршрута')}: {route_name}"
        await message.answer(
            f"✅ {i18n.get('promo_code_applied', user.language).format(discount=discount_text)}{route_info}\n\n"
<<<<<<< HEAD
            f"{i18n.get('promo_select_route_to_use', user.language, default='Выберите маршрут и введите промокод на его экране.')}"
=======
            f"{i18n.get('promo_code_info', user.language, default='Промокод будет применен при оплате выбранного маршрута.')}"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        )
        await state.clear()
@router.callback_query(F.data.startswith("pay:"))
async def process_payment(
    callback: CallbackQuery,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from decimal import Decimal
    from bot.utils.i18n import i18n, get_localized_field
    from bot.repositories.token import TokenRepository
    route_id = int(callback.data.split(":")[1])
    route_repo = RouteRepository(session)
    payment_repo = PaymentRepository(session)
    token_repo = TokenRepository(session)
    has_paid = await payment_repo.has_paid_for_route(user.id, route_id)
    if has_paid:
        await callback.answer(i18n.get("route_already_paid", user.language), show_alert=True)
        return
    route = await route_repo.get(route_id)
    if not route:
        await callback.answer(i18n.get("route_not_found", user.language), show_alert=True)
        return
    data = await state.get_data()
    final_price = data.get("final_price", route.price)
    amount = Decimal(str(final_price))
    balance = await token_repo.get_balance(user.id)
    if balance.balance < amount:
        await callback.answer(
            i18n.get("bank_insufficient_balance", user.language).format(balance=f"{balance.balance:.0f}"),
            show_alert=True
        )
        return
    success, transaction, error_msg = await token_repo.spend(
        user_id=user.id,
        amount=amount,
        route_id=route_id,
        description=i18n.get("bank_tx_purchase", user.language) + ": " + (get_localized_field(route, "name", user.language) or route.name),
    )
    if not success:
        await callback.answer(error_msg, show_alert=True)
        return
    payment = await payment_repo.create_payment(
        user_id=user.id,
        route_id=route_id,
        amount=final_price,
    )
    await payment_repo.mark_success(payment.id, "token_purchase", f"tx_{transaction.id}")
<<<<<<< HEAD
    if getattr(user, "referred_by_id", None):
        try:
            from bot.services.referral_service import ReferralService
            ref_service = ReferralService(session)
            await ref_service.process_purchase(user, route_id, Decimal(str(final_price)))
        except Exception as e:
            import logging
            logging.getLogger(__name__).error(f"Referral reward error: {e}")
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    route = await route_repo.get_with_points(route_id)
    route_name = get_localized_field(route, 'name', user.language)
    route_description = get_localized_field(route, 'description', user.language)
    description = f"📍 <b>{route_name}</b>\n\n"
    if route_description:
        description += f"{route_description}\n\n"
    description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
    description += f"• {i18n.get('points', user.language)}: {len(route.points)}\n"
    if route.estimated_duration:
        description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
    if route.distance:
        description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
<<<<<<< HEAD
    description += f"• {i18n.get('price', user.language)}: {route.price} г\n"
=======
    description += f"• {i18n.get('price', user.language)}: {route.price}₽\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    description += i18n.get("route_paid", user.language)
    try:
        await callback.message.edit_text(
            description,
            reply_markup=UserKeyboards.route_detail(route_id, True, user.language),
            parse_mode="HTML",
        )
    except Exception:
        pass
    await callback.answer(i18n.get("payment_success", user.language))
@router.pre_checkout_query()
async def process_pre_checkout_query(
    pre_checkout_query: PreCheckoutQuery,
    session: AsyncSession,
):
    await pre_checkout_query.answer(ok=True)
@router.message(F.content_type == ContentType.SUCCESSFUL_PAYMENT)
async def process_successful_payment(
    message: Message,
    session: AsyncSession,
    user: User,
    state: FSMContext,
):
    from bot.utils.i18n import i18n
    payment_info = message.successful_payment
    payload = payment_info.invoice_payload
    if payload.startswith("token_deposit:") or payload.startswith("token_deposit_stars:"):
        from bot.repositories.token import TokenRepository
        token_repo = TokenRepository(session)
        parts = payload.split(":")
        deposit_id = int(parts[1])
        deposit = await token_repo.complete_deposit(
            deposit_id,
            payment_id=payment_info.telegram_payment_charge_id
        )
        if deposit:
            await message.answer(
                i18n.get("bank_deposit_success", user.language).format(
                    amount=f"{deposit.amount:.0f}"
                ),
                parse_mode="HTML"
            )
<<<<<<< HEAD
            try:
                from bot.services.admin_notifier import AdminNotifier
                admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
                pm = getattr(deposit.payment_method, "value", None) or getattr(deposit.payment_method, "name", "") or "payment"
                await admin_notifier.notify_balance_deposit(
                    session,
                    user_id=user.id,
                    username=user.username,
                    first_name=user.first_name,
                    amount=deposit.amount,
                    payment_method=str(pm),
                )
            except Exception as e:
                logger.warning("Не удалось отправить уведомление админам о пополнении: %s", e)
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        else:
            await message.answer("✅ Платёж обработан!")
        return
    payload_data = payment_service.parse_payload(payload)
    route_id = payload_data["route_id"]
    payment_repo = PaymentRepository(session)
    payments = await payment_repo.get_all()
    payment = next(
        (
            p
            for p in payments
            if p.user_id == user.id
            and p.route_id == route_id
            and p.status.value == "pending"
        ),
        None,
    )
    if payment:
        await payment_repo.mark_success(
            payment.id,
            payment_info.telegram_payment_charge_id,
            payment_info.provider_payment_charge_id,
        )
    route_repo = RouteRepository(session)
    route = await route_repo.get_with_points(route_id)
    data = await state.get_data()
    route_message_id = data.get("route_message_id")
    if route_message_id:
        try:
            from bot.utils.i18n import i18n, get_localized_field
            route_name = get_localized_field(route, 'name', user.language)
            route_description = get_localized_field(route, 'description', user.language)
            description = f"📍 <b>{route_name}</b>\n\n"
            if route_description:
                description += f"{route_description}\n\n"
            description += f"📊 <b>{i18n.get('route_info', user.language)}</b>\n"
            description += f"• {i18n.get('points', user.language)}: {len(route.points)}\n"
            if route.estimated_duration:
                description += f"• {i18n.get('recommended_time', user.language)}: ~{format_duration(route.estimated_duration)}\n"
            if route.distance:
                description += f"• {i18n.get('distance', user.language)}: {format_distance(route.distance)}\n"
<<<<<<< HEAD
            description += f"• {i18n.get('price', user.language)}: {route.price} г\n"
=======
            description += f"• {i18n.get('price', user.language)}: {route.price}₽\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            description += i18n.get("route_paid", user.language)
            await bot.edit_message_text(
                chat_id=message.chat.id,
                message_id=route_message_id,
                text=description,
                reply_markup=UserKeyboards.route_detail(route_id, True, user.language),
                parse_mode="HTML",
            )
        except Exception as e:
            pass
    from bot.utils.i18n import i18n, get_localized_field
    route_name = get_localized_field(route, 'name', user.language)
    await message.answer(
        i18n.get("payment_success", user.language, default="✅ Payment successful!").format(route_name=route_name) + "\n\n" +
        i18n.get("route_available", user.language, default="Route '{route_name}' is now available.").format(route_name=route_name) + "\n" +
        i18n.get("click_start_quest", user.language, default="Click '▶️ Start Quest' in the message above!")
    )