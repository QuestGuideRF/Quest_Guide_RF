from typing import List, Optional
from decimal import Decimal
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.utils.keyboard import InlineKeyboardBuilder
from bot.models.route import Route
class ModeratorKeyboards:
    @staticmethod
    def main_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_my_routes", lang, default="📍 Мои маршруты"),
                callback_data="mod:my_routes"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_create_route", lang, default="➕ Создать маршрут"),
                callback_data="mod:create_route"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🏙 Создать город",
                callback_data="mod:create_city"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_stats", lang, default="📊 Статистика продаж"),
                callback_data="mod:stats"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_balance", lang, default="💰 Мой баланс"),
                callback_data="mod:balance"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="📩 Связь с администрацией",
                callback_data="mod:contact_admin"
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
    def back_to_mod_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="mod:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def request_status(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_check_status", lang, default="🔄 Проверить статус"),
                callback_data="mod:check_request"
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
    def cancel_request(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="back_to_main"
            )
        )
        return builder.as_markup()
    @staticmethod
    def route_list(routes: List[Route], lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for route in routes:
            route_name = get_localized_field(route, 'name', lang)
            status = "✅" if route.is_published else "⏸️"
            builder.row(
                InlineKeyboardButton(
                    text=f"{status} {route_name}",
                    callback_data=f"mod:route:{route.id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_create_route", lang, default="➕ Создать маршрут"),
                callback_data="mod:create_route"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="mod:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def route_actions(route_id: int, is_published: bool, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_edit_route", lang, default="✏️ Редактировать"),
                callback_data=f"mod:edit_route:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_manage_points", lang, default="📍 Управление точками"),
                callback_data=f"mod:points:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🗑 Удалить маршрут",
                callback_data=f"mod:delete_route:{route_id}"
            )
        )
        if is_published:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("mod_unpublish", lang, default="⏸️ Снять с публикации"),
                    callback_data=f"mod:unpublish:{route_id}"
                )
            )
        else:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("mod_publish", lang, default="🚀 Опубликовать"),
                    callback_data=f"mod:publish:{route_id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_route_stats", lang, default="📊 Статистика"),
                callback_data=f"mod:route_stats:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="mod:my_routes"
            )
        )
        return builder.as_markup()
    @staticmethod
    def balance_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_withdraw", lang, default="💸 Вывести средства"),
                callback_data="mod:withdraw"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("mod_transactions", lang, default="📜 История операций"),
                callback_data="mod:transactions"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data="mod:menu"
            )
        )
        return builder.as_markup()
    @staticmethod
    def city_selection(cities: list, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for city in cities:
            city_name = get_localized_field(city, 'name', lang)
            builder.row(
                InlineKeyboardButton(
                    text=f"🏙 {city_name}",
                    callback_data=f"mod:select_city:{city.id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def route_type_selection(lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="🚶 Пешеходный",
                callback_data="mod:route_type:walking"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🚴 Велосипедный",
                callback_data="mod:route_type:cycling"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="❌ Отмена",
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def skip_or_cancel(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="⏭ Пропустить",
                callback_data="mod:skip_step"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def cancel_only(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def task_type_selection(lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text="📸 Фото", callback_data="mod:task_type:photo"),
            InlineKeyboardButton(text="📝 Ввести текст", callback_data="mod:task_type:text")
        )
        builder.row(
            InlineKeyboardButton(
                text="⏭ Пропустить (фото)",
                callback_data="mod:skip_step"
            )
        )
        from bot.utils.i18n import i18n
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel", lang, default="❌ Отмена"),
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def confirm_route_creation(lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="✅ Создать маршрут",
                callback_data="mod:confirm_create_route"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="❌ Отмена",
                callback_data="mod:cancel_create"
            )
        )
        return builder.as_markup()
    @staticmethod
    def route_created_actions(route_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="📍 Добавить точки",
                callback_data=f"mod:points:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🚀 Опубликовать",
                callback_data=f"mod:publish:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🗑 Удалить маршрут",
                callback_data=f"mod:delete_route:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="◀️ К списку маршрутов",
                callback_data="mod:my_routes"
            )
        )
        return builder.as_markup()
    @staticmethod
    def points_list(points: list, route_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for point in points:
            point_name = get_localized_field(point, 'name', lang)
            builder.row(
                InlineKeyboardButton(
                    text=f"📍 {point.order}. {point_name}",
                    callback_data=f"mod:point:{point.id}"
                )
            )
        builder.row(
            InlineKeyboardButton(
                text="➕ Добавить точку",
                callback_data=f"mod:add_point:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang, default="◀️ Назад"),
                callback_data=f"mod:route:{route_id}"
            )
        )
        return builder.as_markup()
    @staticmethod
    def point_actions(point_id: int, route_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="✏️ Редактировать",
                callback_data=f"mod:edit_point:{point_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="🗑 Удалить",
                callback_data=f"mod:delete_point:{point_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="◀️ Назад",
                callback_data=f"mod:points:{route_id}"
            )
        )
        return builder.as_markup()
    @staticmethod
    def edit_route_menu(route_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="📝 Изменить название",
                callback_data=f"mod:edit_name:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="📄 Изменить описание",
                callback_data=f"mod:edit_desc:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="💰 Изменить цену",
                callback_data=f"mod:edit_price:{route_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="◀️ Назад",
                callback_data=f"mod:route:{route_id}"
            )
        )
        return builder.as_markup()
    @staticmethod
    def confirm_delete(entity_type: str, entity_id: int, back_callback: str, lang: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text="✅ Да, удалить",
                callback_data=f"mod:confirm_delete:{entity_type}:{entity_id}"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text="❌ Отмена",
                callback_data=back_callback
            )
        )
        return builder.as_markup()