from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
from typing import List, Optional
def get_admin_main_menu() -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="🏙 Города", callback_data="admin:cities")],
        [InlineKeyboardButton(text="🗺 Маршруты", callback_data="admin:routes")],
        [InlineKeyboardButton(text="📍 Точки", callback_data="admin:points")],
        [InlineKeyboardButton(text="🎫 Промокоды", callback_data="admin:promo_codes")],
        [InlineKeyboardButton(text="📸 История фото", callback_data="admin:photos")],
        [InlineKeyboardButton(text="👥 Пользователи", callback_data="admin:users")],
        [InlineKeyboardButton(text="🚫 Блокировки", callback_data="admin:bans")],
        [InlineKeyboardButton(text="📊 Статистика", callback_data="admin:stats")],
<<<<<<< HEAD
        [InlineKeyboardButton(text="🤝 Партнерка", callback_data="admin:referral")],
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        [InlineKeyboardButton(text="⚙️ Настройки", callback_data="admin:settings")],
    ])
    return keyboard
def get_cities_menu(cities: List[dict]) -> InlineKeyboardMarkup:
    buttons = []
    for city in cities:
        status = "✅" if city['is_active'] else "❌"
        buttons.append([
            InlineKeyboardButton(
                text=f"{status} {city['name']}",
                callback_data=f"admin:city:{city['id']}"
            )
        ])
    buttons.append([
        InlineKeyboardButton(text="➕ Добавить город", callback_data="admin:city:add")
    ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_city_actions(city_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Редактировать", callback_data=f"admin:city:edit:{city_id}")],
        [InlineKeyboardButton(text="👁 Показать/скрыть", callback_data=f"admin:city:toggle:{city_id}")],
        [InlineKeyboardButton(text="🗑 Удалить", callback_data=f"admin:city:delete:{city_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:cities")],
    ])
    return keyboard
def get_routes_menu(routes: List[dict]) -> InlineKeyboardMarkup:
    buttons = []
    for route in routes:
        status = "✅" if route['is_active'] else "❌"
        buttons.append([
            InlineKeyboardButton(
                text=f"{status} {route['name']} ({route['city_name']})",
                callback_data=f"admin:route:{route['id']}"
            )
        ])
    buttons.append([
        InlineKeyboardButton(text="➕ Добавить маршрут", callback_data="admin:route:add")
    ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_route_actions(route_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Редактировать", callback_data=f"admin:route:edit:{route_id}")],
        [InlineKeyboardButton(text="📍 Точки маршрута", callback_data=f"admin:route:points:{route_id}")],
        [InlineKeyboardButton(text="👁 Показать/скрыть", callback_data=f"admin:route:toggle:{route_id}")],
        [InlineKeyboardButton(text="🗑 Удалить", callback_data=f"admin:route:delete:{route_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:routes")],
    ])
    return keyboard
def get_points_menu(points: List[dict], route_id: int) -> InlineKeyboardMarkup:
    buttons = []
    for point in points:
        buttons.append([
            InlineKeyboardButton(
                text=f"{point['order']}. {point['name']}",
                callback_data=f"admin:point:{point['id']}"
            ),
            InlineKeyboardButton(
                text="✏️",
                callback_data=f"admin:point:edit:{point['id']}"
            ),
            InlineKeyboardButton(
                text="🗑",
                callback_data=f"admin:point:delete:{point['id']}"
            )
        ])
    buttons.append([
        InlineKeyboardButton(text="➕ Добавить точку", callback_data=f"admin:point:add:{route_id}")
    ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:points")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_point_edit_menu(point_id: int, route_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Название", callback_data=f"admin:point:edit_field:name:{point_id}")],
        [InlineKeyboardButton(text="✏️ Задание", callback_data=f"admin:point:edit_field:task:{point_id}")],
        [InlineKeyboardButton(text="✏️ Факт", callback_data=f"admin:point:edit_field:fact:{point_id}")],
<<<<<<< HEAD
=======
        [InlineKeyboardButton(text="✏️ Поза", callback_data=f"admin:point:edit_field:pose:{point_id}")],
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        [InlineKeyboardButton(text="✏️ Мин. людей", callback_data=f"admin:point:edit_field:people:{point_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data=f"admin:point:view:{point_id}")],
    ])
    return keyboard
def get_point_actions(point_id: int, route_id: int, audio_enabled: bool = False) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Редактировать", callback_data=f"admin:point:edit:{point_id}")],
        [InlineKeyboardButton(text="📸 Эталонные фото", callback_data=f"admin:point:refs:{point_id}")],
        [InlineKeyboardButton(text="💡 Подсказки", callback_data=f"admin:point:hints:{point_id}")],
        [InlineKeyboardButton(
            text=f"{'🔊' if audio_enabled else '🔇'} Аудиогид: {'ВКЛ' if audio_enabled else 'ВЫКЛ'}",
            callback_data=f"admin:point:audio_toggle:{point_id}"
        )],
        [InlineKeyboardButton(text="🗑 Удалить", callback_data=f"admin:point:delete:{point_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data=f"admin:route:points:{route_id}")],
    ])
    return keyboard
def get_hints_menu(hints: List[dict], point_id: int) -> InlineKeyboardMarkup:
    buttons = []
    level_names = {1: "💡 Легкая", 2: "🔦 Средняя", 3: "🎯 Детальная"}
    for hint in hints:
        level_text = level_names.get(hint['level'], f"Уровень {hint['level']}")
        map_icon = "🗺" if hint.get('has_map') else ""
        buttons.append([
            InlineKeyboardButton(
                text=f"{level_text} {map_icon}",
                callback_data=f"admin:hint:{hint['id']}"
            ),
            InlineKeyboardButton(
                text="✏️",
                callback_data=f"admin:hint:edit:{hint['id']}"
            ),
            InlineKeyboardButton(
                text="🗑",
                callback_data=f"admin:hint:delete:{hint['id']}"
            )
        ])
    buttons.append([
        InlineKeyboardButton(text="➕ Добавить подсказку", callback_data=f"admin:hint:add:{point_id}")
    ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data=f"admin:point:{point_id}")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_users_pagination(page: int = 1, total_pages: int = 1) -> InlineKeyboardMarkup:
    buttons = []
    nav_buttons = []
    if page > 1:
        nav_buttons.append(InlineKeyboardButton(text="◀️", callback_data=f"admin:users:page:{page-1}"))
    nav_buttons.append(InlineKeyboardButton(text=f"{page}/{total_pages}", callback_data="admin:users:page:current"))
    if page < total_pages:
        nav_buttons.append(InlineKeyboardButton(text="▶️", callback_data=f"admin:users:page:{page+1}"))
    if nav_buttons:
        buttons.append(nav_buttons)
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_user_actions(user_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="📊 Статистика", callback_data=f"admin:user:stats:{user_id}")],
        [InlineKeyboardButton(text="🔄 Сбросить прогресс", callback_data=f"admin:user:reset:{user_id}")],
        [InlineKeyboardButton(text="🚫 Заблокировать", callback_data=f"admin:user:ban:{user_id}")],
        [InlineKeyboardButton(text="✅ Разблокировать", callback_data=f"admin:user:unban:{user_id}")],
        [InlineKeyboardButton(text="💬 Отправить сообщение", callback_data=f"admin:user:message:{user_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:users")],
    ])
    return keyboard
def get_photo_history_pagination(page: int = 1, total_pages: int = 1) -> InlineKeyboardMarkup:
    buttons = []
    nav_buttons = []
    if page > 1:
        nav_buttons.append(InlineKeyboardButton(text="◀️", callback_data=f"admin:photos:page:{page-1}"))
    if total_pages > 1:
        nav_buttons.append(InlineKeyboardButton(text=f"{page}/{total_pages}", callback_data="admin:photos:page:current"))
    if page < total_pages:
        nav_buttons.append(InlineKeyboardButton(text="▶️", callback_data=f"admin:photos:page:{page+1}"))
    if nav_buttons:
        buttons.append(nav_buttons)
    buttons.append([
        InlineKeyboardButton(text="🔄 Обновить", callback_data="admin:photos")
    ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
    ])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
def get_confirm_keyboard(action: str, entity_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(text="✅ Да", callback_data=f"admin:confirm:{action}:{entity_id}"),
            InlineKeyboardButton(text="❌ Нет", callback_data=f"admin:cancel:{action}:{entity_id}")
        ]
    ])
    return keyboard
def get_back_to_menu() -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="« В главное меню", callback_data="admin:menu")]
    ])
    return keyboard
def bans_menu() -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="🔍 Заблокировать пользователя", callback_data="admin:bans:search")],
        [InlineKeyboardButton(text="📋 Список заблокированных", callback_data="admin:bans:list")],
        [InlineKeyboardButton(text="🔓 Разблокировать", callback_data="admin:bans:unban")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:menu")]
    ])
    return keyboard
def ban_duration_menu(user_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="⏱ 1 час", callback_data=f"admin:ban:duration:{user_id}:1h")],
        [InlineKeyboardButton(text="📅 1 день", callback_data=f"admin:ban:duration:{user_id}:1d")],
        [InlineKeyboardButton(text="📆 1 месяц", callback_data=f"admin:ban:duration:{user_id}:1m")],
        [InlineKeyboardButton(text="📋 1 год", callback_data=f"admin:ban:duration:{user_id}:1y")],
        [InlineKeyboardButton(text="🚫 Навсегда", callback_data=f"admin:ban:duration:{user_id}:forever")],
        [InlineKeyboardButton(text="❌ Отмена", callback_data="admin:bans")]
    ])
    return keyboard
def back_to_bans_menu() -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="« Назад", callback_data="admin:bans")]
    ])
<<<<<<< HEAD
    return keyboard
def moderator_request_actions(user_id: int, request_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(text="✅ Принять", callback_data=f"admin:mod_request:approve:{user_id}:{request_id}"),
            InlineKeyboardButton(text="❌ Отклонить", callback_data=f"admin:mod_request:reject:{user_id}:{request_id}")
        ]
    ])
    return keyboard
def route_moderation_actions(route_id: int, moderator_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(text="✅ Одобрить", callback_data=f"admin:route_mod:approve:{route_id}:{moderator_id}"),
            InlineKeyboardButton(text="❌ Отклонить", callback_data=f"admin:route_mod:reject:{route_id}:{moderator_id}")
        ],
        [
            InlineKeyboardButton(text="👁 Посмотреть маршрут", url=f"https://questguiderf.ru/admin/routes/edit.php?id={route_id}")
        ]
    ])
    return keyboard
def reply_to_moderator(moderator_telegram_id: int) -> InlineKeyboardMarkup:
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(text="✉️ Ответить", callback_data=f"admin:reply_mod:{moderator_telegram_id}")
        ]
    ])
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    return keyboard