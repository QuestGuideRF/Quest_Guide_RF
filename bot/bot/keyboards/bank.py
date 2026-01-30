from typing import List, Optional, Set
from decimal import Decimal
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.utils.keyboard import InlineKeyboardBuilder
from bot.models.city import City
from bot.models.route import Route
class BankKeyboards:
    @staticmethod
    def main_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_deposit", lang, default="💳 Пополнить"),
                callback_data="bank:deposit"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_transfer", lang, default="💸 Перевести"),
                callback_data="bank:transfer"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_buy_tour", lang, default="🎫 Купить экскурсию"),
                callback_data="bank:buy_tour"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_history", lang, default="📜 История"),
                callback_data="bank:history"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="back_to_main"
            )
        )
        return builder.as_markup()
    @staticmethod
    def deposit_methods(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_yookassa", lang, default="💳 ЮKassa (карта)"),
                callback_data="bank:deposit:yookassa"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_telegram_stars", lang, default="⭐ Telegram Stars"),
                callback_data="bank:deposit:stars"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="bank:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def deposit_amounts(payment_method: str, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        amounts = [100, 250, 500, 1000, 2000, 5000]
        for i in range(0, len(amounts), 2):
            row_buttons = []
            for amount in amounts[i:i+2]:
                row_buttons.append(
                    InlineKeyboardButton(
                        text=f"💰 {amount} ₽",
                        callback_data=f"bank:deposit:{payment_method}:{amount}"
                    )
                )
            builder.row(*row_buttons)
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_custom_amount", lang, default="✏️ Своя сумма"),
                callback_data=f"bank:deposit:{payment_method}:custom"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="bank:deposit"
            )
        )
        return builder.as_markup()
    @staticmethod
    def confirm_deposit(amount: int, payment_method: str, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_confirm_deposit", lang, default="✅ Подтвердить"),
                callback_data=f"bank:confirm_deposit:{payment_method}:{amount}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="bank:deposit"
            )
        )
        return builder.as_markup()
    @staticmethod
    def transfer_confirm(recipient_id: int, amount: Decimal, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_confirm_transfer", lang, default="✅ Подтвердить перевод"),
                callback_data=f"bank:confirm_transfer:{recipient_id}:{amount}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="bank:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def city_list_for_purchase(cities: List[City], lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for city in cities:
            city_name = get_localized_field(city, 'name', lang)
            builder.row(
                InlineKeyboardButton(
                    text=f"🏙 {city_name}",
                    callback_data=f"bank:city:{city.id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="bank:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def route_list_for_purchase(
        routes: List[Route],
        city_id: int,
        user_balance: Decimal,
        lang: str = "ru",
        paid_route_ids: Optional[Set[int]] = None,
    ) -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        paid_route_ids = paid_route_ids or set()
        builder = InlineKeyboardBuilder()
        bought_label = i18n.get("bank_already_purchased_short", lang, default="(куплено)")
        for route in routes:
            route_name = get_localized_field(route, 'name', lang)
            price = route.price
            if route.id in paid_route_ids:
                icon = "✅"
                suffix = f" {bought_label}"
            elif user_balance >= price:
                icon = "✅"
                suffix = ""
            else:
                icon = "❌"
                suffix = ""
            builder.row(
                InlineKeyboardButton(
                    text=f"{icon} {route_name} - {price}₽{suffix}",
                    callback_data=f"bank:route:{route.id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="bank:buy_tour"
            )
        )
        return builder.as_markup()
    @staticmethod
    def confirm_purchase(route_id: int, price: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("bank_confirm_purchase", lang, price=price),
                callback_data=f"bank:confirm_purchase:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="bank:buy_tour"
            )
        )
        return builder.as_markup()
    @staticmethod
    def back_to_bank(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="bank:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def cancel_transfer(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="bank:menu"
            )
        )
        return builder.as_markup()