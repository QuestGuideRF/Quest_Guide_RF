import logging
import os
import shutil
from pathlib import Path
from datetime import datetime, timedelta
from aiogram import Router, F
from aiogram.filters import Command
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.fsm.context import FSMContext
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text, func
from bot.loader import bot, dp
from bot.repositories.progress import ProgressRepository
from bot.repositories.point import PointRepository
from bot.repositories.city import CityRepository
from bot.repositories.route import RouteRepository
from bot.repositories.user import UserRepository
from bot.keyboards.admin import (
    get_admin_main_menu,
    get_cities_menu,
    get_city_actions,
    get_routes_menu,
    get_route_actions,
    get_points_menu,
    get_point_actions,
    get_users_pagination,
    get_user_actions,
    get_photo_history_pagination,
    get_confirm_keyboard,
    get_back_to_menu
)
from bot.fsm.admin_states import (
    AdminCityStates,
    AdminRouteStates,
    AdminPointStates,
    AdminUserStates,
    AdminSettingsStates,
<<<<<<< HEAD
    AdminPromoCodeStates,
    AdminReferralStates,
)
from bot.loader import config
=======
    AdminPromoCodeStates
)
from bot.config import load_config
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
from bot.utils.safe_edit import safe_edit_text
from bot.services.admin_notifier import AdminNotifier
logger = logging.getLogger(__name__)
router = Router()
<<<<<<< HEAD
=======
config = load_config()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
def is_admin(user_id: int) -> bool:
    return user_id in config.bot.admin_ids
@router.message(Command("admin"))
async def admin_menu_command(message: Message, session: AsyncSession):
    from bot.utils.i18n import i18n
    from bot.repositories.user import UserRepository
    if not is_admin(message.from_user.id):
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(message.from_user.id)
        language = user.language if user else 'ru'
        await message.answer(i18n.get("no_admin_rights", language))
        return
    stats = await get_admin_stats(session)
    text = (
        f"🔐 <b>Админ-панель QuestGuideRF</b>\n\n"
        f"👥 Пользователей: {stats['total_users']}\n"
        f"🗺 Маршрутов: {stats['total_routes']}\n"
        f"📍 Точек: {stats['total_points']}\n"
        f"📸 Фото на проверке: {stats['pending_photos']}\n\n"
        f"Выберите раздел:"
    )
    await message.answer(text, reply_markup=get_admin_main_menu(), parse_mode="HTML")
@router.callback_query(F.data == "admin:menu")
async def admin_menu_callback(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    stats = await get_admin_stats(session)
    text = (
        f"🔐 <b>Админ-панель QuestGuideRF</b>\n\n"
        f"👥 Пользователей: {stats['total_users']}\n"
        f"🗺 Маршрутов: {stats['total_routes']}\n"
        f"📍 Точек: {stats['total_points']}\n"
        f"📸 Фото на проверке: {stats['pending_photos']}\n\n"
        f"Выберите раздел:"
    )
    await safe_edit_text(callback, text, reply_markup=get_admin_main_menu())
    await callback.answer()
@router.callback_query(F.data == "admin:cities")
async def admin_cities_list(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_repo = CityRepository(session)
    cities = await city_repo.get_all()
    text = f"🏙 <b>Управление городами</b>\n\nВсего городов: {len(cities)}"
    await callback.message.edit_text(
        text,
        reply_markup=get_cities_menu([{
            'id': c.id,
            'name': c.name,
            'is_active': c.is_active
        } for c in cities]),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:city:") & ~F.data.contains("add") & ~F.data.contains("edit") & ~F.data.contains("toggle") & ~F.data.contains("delete"))
async def admin_city_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_id = int(callback.data.split(":")[-1])
    city_repo = CityRepository(session)
    city = await city_repo.get(city_id)
    if not city:
        await callback.answer("❌ Город не найден")
        return
    result = await session.execute(
        text("SELECT COUNT(*) FROM routes WHERE city_id = :city_id"),
        {"city_id": city_id}
    )
    routes_count = result.scalar()
    msg_text = (
        f"🏙 <b>{city.name}</b>\n\n"
        f"📝 Описание: {city.description or 'Нет'}\n"
        f"📊 Статус: {'✅ Активен' if city.is_active else '❌ Неактивен'}\n"
        f"🗺 Маршрутов: {routes_count}\n"
    )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_city_actions(city_id),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:city:toggle:"))
async def admin_city_toggle(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_id = int(callback.data.split(":")[-1])
    await session.execute(
        text("UPDATE cities SET is_active = NOT is_active WHERE id = :city_id"),
        {"city_id": city_id}
    )
    await session.commit()
    await callback.answer("✅ Статус изменён")
    await admin_city_view(callback, session)
@router.callback_query(F.data == "admin:city:add")
async def admin_city_add_start(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await callback.message.answer(
        "🏙 <b>Добавление города</b>\n\n"
        "Введите название города:",
        parse_mode="HTML"
    )
    await state.set_state(AdminCityStates.name)
    await callback.answer()
@router.message(AdminCityStates.name)
async def admin_city_add_name(message: Message, state: FSMContext):
    await state.update_data(name=message.text)
    await message.answer(
        f"Название: <b>{message.text}</b>\n\n"
        "Теперь введите описание города:",
        parse_mode="HTML"
    )
    await state.set_state(AdminCityStates.description)
@router.message(AdminCityStates.description)
async def admin_city_add_description(message: Message, state: FSMContext, session: AsyncSession):
    data = await state.get_data()
    result = await session.execute(
<<<<<<< HEAD
        text("INSERT INTO cities (name, description) VALUES (:name, :description)"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {
            "name": data['name'],
            "description": message.text
        }
    )
    await session.commit()
    await message.answer(
        f"✅ Город <b>{data['name']}</b> добавлен!",
        parse_mode="HTML"
    )
    await state.clear()
@router.callback_query(F.data.startswith("admin:city:edit:"))
async def admin_city_edit_start(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_id = int(callback.data.split(":")[-1])
    city_repo = CityRepository(session)
    city = await city_repo.get(city_id)
    if not city:
        await callback.answer("❌ Город не найден")
        return
    await state.update_data(city_id=city_id, old_name=city.name)
    await callback.message.answer(
        f"✏️ <b>Редактирование города</b>\n\n"
        f"Текущее название: <b>{city.name}</b>\n"
        f"Текущее описание: {city.description or 'Нет'}\n\n"
        f"Введите новое название (или /skip для пропуска):",
        parse_mode="HTML"
    )
    await state.set_state(AdminCityStates.name)
    await callback.answer()
@router.callback_query(F.data.startswith("admin:city:delete:"))
async def admin_city_delete(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT COUNT(*) FROM routes WHERE city_id = :city_id"),
        {"city_id": city_id}
    )
    routes_count = result.scalar()
    if routes_count > 0:
        await callback.answer(f"❌ Нельзя удалить. У города есть {routes_count} маршрутов", show_alert=True)
        return
    await session.execute(
        text("DELETE FROM cities WHERE id = :city_id"),
        {"city_id": city_id}
    )
    await session.commit()
    await callback.answer("✅ Город удалён")
    await admin_cities_list(callback, session)
@router.callback_query(F.data == "admin:routes")
async def admin_routes_list(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT r.id, r.name, r.is_active, c.name as city_name FROM routes r LEFT JOIN cities c ON r.city_id = c.id ORDER BY r.name")
=======
        text()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    routes = [dict(row._mapping) for row in result.fetchall()]
    msg_text = f"🗺 <b>Управление маршрутами</b>\n\nВсего маршрутов: {len(routes)}"
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_routes_menu(routes),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:route:") & ~F.data.contains("add") & ~F.data.contains("edit") & ~F.data.contains("toggle") & ~F.data.contains("delete") & ~F.data.contains("points"))
async def admin_route_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
    from sqlalchemy.orm import selectinload
    from sqlalchemy import select
    from bot.models.route import Route
    result = await session.execute(
        select(Route)
        .options(selectinload(Route.city))
        .where(Route.id == route_id)
    )
    route = result.scalar_one_or_none()
    if not route:
        await callback.answer("❌ Маршрут не найден")
        return
    result = await session.execute(
        text("SELECT COUNT(*) FROM points WHERE route_id = :route_id"),
        {"route_id": route_id}
    )
    points_count = result.scalar()
    result = await session.execute(
        text("SELECT COUNT(*) FROM user_progress WHERE route_id = :route_id AND status = 'completed'"),
        {"route_id": route_id}
    )
    completed_count = result.scalar()
    msg_text = (
        f"🗺 <b>{route.name}</b>\n\n"
        f"🏙 Город: {route.city.name}\n"
        f"📝 Описание: {route.description or 'Нет'}\n"
        f"🚶 Тип: {route.route_type.value}\n"
        f"📏 Расстояние: {route.distance}м\n"
        f"⏱ Время: {route.estimated_duration}мин\n"
<<<<<<< HEAD
        f"💰 Цена: {route.price} г\n"
=======
        f"💰 Цена: {route.price}₽\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        f"📊 Статус: {'✅ Активен' if route.is_active else '❌ Неактивен'}\n"
        f"📍 Точек: {points_count}\n"
        f"✅ Пройдено раз: {completed_count}\n"
    )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_route_actions(route_id),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:route:edit:"))
async def admin_route_edit(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
<<<<<<< HEAD
=======
    from bot.config import load_config
    config = load_config()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    web_url = f"{config.web.site_url}/admin/routes/edit.php?id={route_id}"
    await callback.message.answer(
        f"✏️ <b>Редактирование маршрута</b>\n\n"
        f"Для редактирования маршрута используйте веб-интерфейс:\n\n"
        f"🔗 <a href='{web_url}'>Открыть в админ-панели</a>\n\n"
        f"Или перейдите в админ-панель и выберите:\n"
        f"🗺 Управление маршрутами → Маршрут #{route_id}",
        parse_mode="HTML",
        disable_web_page_preview=True
    )
    await callback.answer("ℹ️ Используйте веб-интерфейс для редактирования")
@router.callback_query(F.data == "admin:points")
async def admin_points_list(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT r.id, r.name, r.is_active, c.name as city_name, (SELECT COUNT(*) FROM points p WHERE p.route_id = r.id) as points_count FROM routes r LEFT JOIN cities c ON r.city_id = c.id ORDER BY r.name")
=======
        text()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    routes = result.fetchall()
    if not routes:
        msg_text = "📍 <b>Управление точками</b>\n\n❌ Нет маршрутов. Сначала создайте маршрут."
        await callback.message.edit_text(
            msg_text,
            reply_markup=get_back_to_menu(),
            parse_mode="HTML"
        )
        await callback.answer()
        return
    msg_text = f"📍 <b>Управление точками</b>\n\nВыберите маршрут для просмотра точек:\n\n"
    buttons = []
    for route in routes:
        status = "✅" if route.is_active else "❌"
        buttons.append([
            InlineKeyboardButton(
                text=f"{status} {route.name} ({route.city_name}) - {route.points_count} точек",
                callback_data=f"admin:route:points:{route.id}"
            )
        ])
    buttons.append([
        InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
    ])
    keyboard = InlineKeyboardMarkup(inline_keyboard=buttons)
    await callback.message.edit_text(
        msg_text,
        reply_markup=keyboard,
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:route:points:"))
async def admin_route_points(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
    point_repo = PointRepository(session)
    points = await point_repo.get_by_route(route_id)
    msg_text = f"📍 <b>Точки маршрута</b>\n\nВсего точек: {len(points)}"
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_points_menu([{
            'id': p.id,
            'name': p.name,
            'order': p.order
        } for p in points], route_id),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:route:toggle:"))
async def admin_route_toggle(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
    await session.execute(
        text("UPDATE routes SET is_active = NOT is_active WHERE id = :route_id"),
        {"route_id": route_id}
    )
    await session.commit()
    await callback.answer("✅ Статус изменён")
    await admin_route_view(callback, session)
@router.callback_query(F.data.startswith("admin:route:delete:"))
async def admin_route_delete(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT COUNT(*) FROM user_progress WHERE route_id = :route_id AND status = 'in_progress'"),
        {"route_id": route_id}
    )
    active_count = result.scalar()
    if active_count > 0:
        await callback.answer(f"❌ Нельзя удалить. {active_count} пользователей проходят маршрут", show_alert=True)
        return
    await session.execute(text("DELETE FROM reference_images WHERE point_id IN (SELECT id FROM points WHERE route_id = :route_id)"), {"route_id": route_id})
    await session.execute(text("DELETE FROM user_photos WHERE point_id IN (SELECT id FROM points WHERE route_id = :route_id)"), {"route_id": route_id})
    await session.execute(text("DELETE FROM points WHERE route_id = :route_id"), {"route_id": route_id})
    await session.execute(text("DELETE FROM user_progress WHERE route_id = :route_id"), {"route_id": route_id})
    await session.execute(text("DELETE FROM routes WHERE id = :route_id"), {"route_id": route_id})
    await session.commit()
    await callback.answer("✅ Маршрут удалён")
    await admin_routes_list(callback, session)
@router.callback_query(F.data.startswith("admin:point:") & ~F.data.contains("add") & ~F.data.contains("edit") & ~F.data.contains("delete") & ~F.data.contains("refs") & ~F.data.contains("audio_toggle") & ~F.data.contains("hints"))
async def admin_point_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    point_repo = PointRepository(session)
    point = await point_repo.get(point_id)
    if not point:
        await callback.answer("❌ Точка не найдена")
        return
    result = await session.execute(
        text("SELECT COUNT(*) FROM user_photos WHERE point_id = :point_id"),
        {"point_id": point_id}
    )
    photos_count = result.scalar()
    result = await session.execute(
        text("SELECT COUNT(*) FROM reference_images WHERE point_id = :point_id"),
        {"point_id": point_id}
    )
    refs_count = result.scalar()
    msg_text = (
        f"📍 <b>{point.order}. {point.name}</b>\n\n"
        f"📋 Задание: {get_first_task_text(point) or 'Нет'}\n"
        f"💡 Факт: {point.fact_text or 'Нет'}\n"
<<<<<<< HEAD
=======
        f"🤸 Поза: {point.require_pose or 'Не требуется'}\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        f"🎧 Аудиогид: {'✅ Включен' if point.audio_enabled else '❌ Выключен'}\n"
        f"📸 Фото пользователей: {photos_count}\n"
        f"🖼 Эталонных фото: {refs_count}\n"
    )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_point_actions(point_id, point.route_id, point.audio_enabled),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:point:edit:"))
async def admin_point_edit(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT route_id FROM points WHERE id = :point_id"),
        {"point_id": point_id}
    )
    route_id = result.scalar()
    if not route_id:
        await callback.answer("❌ Точка не найдена")
        return
<<<<<<< HEAD
=======
    from bot.config import load_config
    config = load_config()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    web_url = f"{config.web.site_url}/admin/points/edit.php?id={point_id}"
    await callback.message.answer(
        f"✏️ <b>Редактирование точки</b>\n\n"
        f"Для редактирования точки используйте веб-интерфейс:\n\n"
        f"🔗 <a href='{web_url}'>Открыть в админ-панели</a>\n\n"
        f"Или перейдите в админ-панель и выберите:\n"
        f"📍 Управление точками → Точка #{point_id}",
        parse_mode="HTML",
        disable_web_page_preview=True
    )
    await callback.answer("ℹ️ Используйте веб-интерфейс для редактирования")
@router.callback_query(F.data.startswith("admin:point:delete:"))
async def admin_point_delete(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT route_id FROM points WHERE id = :point_id"),
        {"point_id": point_id}
    )
    route_id = result.scalar()
    await session.execute(text("DELETE FROM reference_images WHERE point_id = :point_id"), {"point_id": point_id})
    await session.execute(text("DELETE FROM user_photos WHERE point_id = :point_id"), {"point_id": point_id})
    await session.execute(text("DELETE FROM points WHERE id = :point_id"), {"point_id": point_id})
    await session.commit()
    await callback.answer("✅ Точка удалена")
    class TempCallback:
        def __init__(self, original_callback, new_data):
            self.message = original_callback.message
            self.from_user = original_callback.from_user
            self.data = new_data
            self.id = original_callback.id
            self.chat_instance = original_callback.chat_instance
            self.answer = original_callback.answer
    temp_callback = TempCallback(callback, f"admin:route:points:{route_id}")
    await admin_route_points(temp_callback, session)
@router.callback_query(F.data.startswith("admin:point:audio_toggle:"))
async def admin_point_audio_toggle(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT audio_enabled, route_id FROM points WHERE id = :point_id"),
        {"point_id": point_id}
    )
    row = result.fetchone()
    if not row:
        await callback.answer("❌ Точка не найдена")
        return
    audio_enabled, route_id = row
    new_audio_enabled = not audio_enabled
    await session.execute(
        text("UPDATE points SET audio_enabled = :audio_enabled WHERE id = :point_id"),
        {"audio_enabled": 1 if new_audio_enabled else 0, "point_id": point_id}
    )
    await session.commit()
    await callback.answer(f"✅ Аудиогид {'включен' if new_audio_enabled else 'выключен'}")
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT p.id, p.order, p.name, p.fact_text, p.audio_enabled, (SELECT COUNT(*) FROM user_photos WHERE point_id = p.id) as photos_count, (SELECT COUNT(*) FROM reference_images WHERE point_id = p.id) as refs_count FROM points p WHERE p.id = :point_id"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"point_id": point_id}
    )
    point = result.fetchone()
    if point:
        msg_text = (
            f"📍 <b>{point.order}. {point.name}</b>\n\n"
            f"📋 Задание: {get_first_task_text(point) or 'Нет'}\n"
            f"💡 Факт: {point.fact_text or 'Нет'}\n"
<<<<<<< HEAD
=======
            f"🤸 Поза: {point.require_pose or 'Не требуется'}\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            f"🎧 Аудиогид: {'✅ Включен' if new_audio_enabled else '❌ Выключен'}\n"
            f"📸 Фото пользователей: {point.photos_count}\n"
            f"🖼 Эталонных фото: {point.refs_count}\n"
        )
        await callback.message.edit_text(
            msg_text,
            reply_markup=get_point_actions(point_id, route_id, new_audio_enabled),
            parse_mode="HTML"
        )
@router.callback_query(F.data.startswith("admin:point:refs:"))
async def admin_point_refs(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT id, file_path, created_at FROM reference_images WHERE point_id = :point_id ORDER BY created_at DESC"),
        {"point_id": point_id}
    )
    refs = result.fetchall()
    msg_text = f"🖼 <b>Эталонные фото</b>\n\nВсего: {len(refs)}\n\n"
    if refs:
        msg_text += "Для добавления нового фото отправьте его в ответ.\n"
        msg_text += "Для удаления используйте команду /delref <id>"
    else:
        msg_text += "Эталонных фото нет. Отправьте фото для добавления."
    await callback.message.answer(msg_text, parse_mode="HTML")
    await callback.answer()
@router.callback_query(F.data.startswith("admin:users"))
async def admin_users_list(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    page = 1
    if "page:" in callback.data:
        page = int(callback.data.split(":")[-1])
    per_page = 10
    offset = (page - 1) * per_page
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT u.id, u.telegram_id, u.first_name, u.username, u.created_at, (SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id) as routes_count, (SELECT COUNT(*) FROM user_photos up WHERE up.user_id = u.id) as photos_count FROM users u ORDER BY u.id LIMIT :limit OFFSET :offset"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"limit": per_page, "offset": offset}
    )
    users = result.fetchall()
    result = await session.execute(text("SELECT COUNT(*) FROM users"))
    total = result.scalar()
    total_pages = (total + per_page - 1) // per_page
    msg_text = f"👥 <b>Пользователи</b>\n\nВсего: {total}\nСтраница {page}/{total_pages}\n\n"
    for user in users:
        msg_text += (
            f"👤 {user.first_name} (@{user.username or 'нет'})\n"
            f"   ID: {user.telegram_id}\n"
            f"   🗺 Маршрутов: {user.routes_count} | 📸 Фото: {user.photos_count}\n\n"
        )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_users_pagination(page, total_pages),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:user:") & ~F.data.contains("stats") & ~F.data.contains("reset") & ~F.data.contains("ban") & ~F.data.contains("unban") & ~F.data.contains("message"))
async def admin_user_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    user_telegram_id = int(callback.data.split(":")[-1])
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT u.id, u.telegram_id, u.first_name, u.username, u.created_at, (SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id AND up.status = 'completed') as completed_routes, (SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id AND up.status = 'in_progress') as active_routes, (SELECT COUNT(*) FROM user_photos up WHERE up.user_id = u.id) as photos_count FROM users u WHERE u.telegram_id = :telegram_id"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"telegram_id": user_telegram_id}
    )
    user = result.fetchone()
    if not user:
        await callback.answer("❌ Пользователь не найден")
        return
<<<<<<< HEAD
    user_text = (
=======
    text = (
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        f"👤 <b>{user.first_name}</b>\n"
        f"@{user.username or 'нет username'}\n\n"
        f"🆔 Telegram ID: {user.telegram_id}\n"
        f"📅 Регистрация: {user.created_at.strftime('%d.%m.%Y')}\n\n"
        f"📊 <b>Статистика:</b>\n"
        f"✅ Завершено маршрутов: {user.completed_routes}\n"
        f"🔄 В процессе: {user.active_routes}\n"
        f"📸 Загружено фото: {user.photos_count}\n"
    )
    await callback.message.edit_text(
<<<<<<< HEAD
        user_text,
=======
        text,
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        reply_markup=get_user_actions(user_telegram_id),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:user:reset:"))
async def admin_user_reset(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    user_telegram_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT id FROM users WHERE telegram_id = :telegram_id"),
        {"telegram_id": user_telegram_id}
    )
    user_row = result.fetchone()
    if not user_row:
        await callback.answer("❌ Пользователь не найден")
        return
    user_id = user_row[0]
    await session.execute(text("DELETE FROM user_photos WHERE user_id = :user_id"), {"user_id": user_id})
    await session.execute(text("DELETE FROM user_progress WHERE user_id = :user_id"), {"user_id": user_id})
    await session.execute(text("DELETE FROM user_achievements WHERE user_id = :user_id"), {"user_id": user_id})
    await session.commit()
    try:
        await bot.send_message(
            user_telegram_id,
            "⚠️ <b>Ваш прогресс был сброшен администратором</b>\n\n"
            "Вы можете начать заново!",
            parse_mode="HTML"
        )
    except Exception as e:
        logger.error(f"Ошибка отправки уведомления: {e}")
    await callback.answer("✅ Прогресс сброшен")
    from aiogram.types import CallbackQuery as CallbackQueryType
    fake_callback = CallbackQueryType(
        id=callback.id,
        from_user=callback.from_user,
        chat_instance=callback.chat_instance,
        data=f"admin:user:{user_telegram_id}",
        message=callback.message
    )
    await admin_user_view(fake_callback, session)
@router.callback_query(F.data.startswith("admin:user:ban:"))
async def admin_user_ban(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    user_telegram_id = int(callback.data.split(":")[-1])
    await session.execute(
        text("UPDATE users SET is_banned = 1 WHERE telegram_id = :telegram_id"),
        {"telegram_id": user_telegram_id}
    )
    await session.commit()
    try:
        await bot.send_message(
            user_telegram_id,
            "🚫 <b>Вы были заблокированы администратором</b>\n\n"
            "Обратитесь в поддержку для уточнения причины.",
            parse_mode="HTML"
        )
    except Exception as e:
        logger.error(f"Ошибка отправки уведомления: {e}")
    await callback.answer("✅ Пользователь заблокирован")
@router.callback_query(F.data.startswith("admin:user:unban:"))
async def admin_user_unban(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    user_telegram_id = int(callback.data.split(":")[-1])
    await session.execute(
        text("UPDATE users SET is_banned = 0 WHERE telegram_id = :telegram_id"),
        {"telegram_id": user_telegram_id}
    )
    await session.commit()
    try:
        await bot.send_message(
            user_telegram_id,
            "✅ <b>Вы были разблокированы</b>\n\n"
            "Теперь вы снова можете пользоваться ботом!",
            parse_mode="HTML"
        )
    except Exception as e:
        logger.error(f"Ошибка отправки уведомления: {e}")
    await callback.answer("✅ Пользователь разблокирован")
@router.callback_query(F.data.startswith("admin:user:message:"))
async def admin_user_message_start(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    user_telegram_id = int(callback.data.split(":")[-1])
    await state.update_data(target_user_id=user_telegram_id)
    await callback.message.answer(
        "💬 <b>Отправка сообщения пользователю</b>\n\n"
        "Введите текст сообщения:",
        parse_mode="HTML"
    )
    await state.set_state(AdminUserStates.message)
    await callback.answer()
@router.message(AdminUserStates.message)
async def admin_user_message_send(message: Message, state: FSMContext):
    data = await state.get_data()
    target_user_id = data['target_user_id']
    try:
        await bot.send_message(
            target_user_id,
            f"📨 <b>Сообщение от администратора:</b>\n\n{message.text}",
            parse_mode="HTML"
        )
        await message.answer("✅ Сообщение отправлено!")
    except Exception as e:
        await message.answer(f"❌ Ошибка: {e}")
        logger.error(f"Ошибка отправки сообщения: {e}")
    await state.clear()
@router.callback_query(F.data == "admin:route:add")
async def admin_route_add_start(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    city_repo = CityRepository(session)
    cities = await city_repo.get_all()
    if not cities:
        await callback.answer("❌ Сначала добавьте хотя бы один город", show_alert=True)
        return
    cities_text = "\n".join([f"{c.id}. {c.name}" for c in cities])
    await callback.message.answer(
        f"🗺 <b>Добавление маршрута</b>\n\n"
        f"Доступные города:\n{cities_text}\n\n"
        f"Введите ID города:",
        parse_mode="HTML"
    )
    await state.set_state(AdminRouteStates.city)
    await callback.answer()
@router.message(AdminRouteStates.city)
async def admin_route_add_city(message: Message, state: FSMContext, session: AsyncSession):
    try:
        city_id = int(message.text)
        city_repo = CityRepository(session)
        city = await city_repo.get(city_id)
        if not city:
            await message.answer("❌ Город не найден. Попробуйте снова:")
            return
        await state.update_data(city_id=city_id, city_name=city.name)
        await message.answer(
            f"Город: <b>{city.name}</b>\n\n"
            "Теперь введите название маршрута:",
            parse_mode="HTML"
        )
        await state.set_state(AdminRouteStates.name)
    except ValueError:
        await message.answer("❌ Введите число (ID города)")
@router.message(AdminRouteStates.name)
async def admin_route_add_name(message: Message, state: FSMContext):
    await state.update_data(name=message.text)
    await message.answer(
        f"Название: <b>{message.text}</b>\n\n"
        "Введите описание маршрута:",
        parse_mode="HTML"
    )
    await state.set_state(AdminRouteStates.description)
@router.message(AdminRouteStates.description)
async def admin_route_add_description(message: Message, state: FSMContext):
    await state.update_data(description=message.text)
    await message.answer(
        "Выберите тип маршрута:\n\n"
        "1 - Пеший (walking)\n"
        "2 - Велосипедный (cycling)\n\n"
        "Введите номер:"
    )
    await state.set_state(AdminRouteStates.route_type)
@router.message(AdminRouteStates.route_type)
async def admin_route_add_type(message: Message, state: FSMContext):
    route_types = {"1": "walking", "2": "cycling"}
    route_type = route_types.get(message.text)
    if not route_type:
        await message.answer("❌ Введите 1 или 2")
        return
    await state.update_data(route_type=route_type)
    await message.answer(
        f"Тип: <b>{route_type}</b>\n\n"
        "Введите расстояние в метрах (например: 2500):",
        parse_mode="HTML"
    )
    await state.set_state(AdminRouteStates.distance)
@router.message(AdminRouteStates.distance)
async def admin_route_add_distance(message: Message, state: FSMContext):
    try:
        distance = int(message.text)
        await state.update_data(distance=distance)
        await message.answer(
            f"Расстояние: <b>{distance}м</b>\n\n"
            "Введите ориентировочное время в минутах (например: 60):",
            parse_mode="HTML"
        )
        await state.set_state(AdminRouteStates.estimated_duration)
    except ValueError:
        await message.answer("❌ Введите число (метры)")
@router.message(AdminRouteStates.estimated_duration)
async def admin_route_add_duration(message: Message, state: FSMContext):
    try:
        duration = int(message.text)
        await state.update_data(estimated_duration=duration)
        await message.answer(
            f"Время: <b>{duration}мин</b>\n\n"
            "Введите цену в рублях (например: 500):",
            parse_mode="HTML"
        )
        await state.set_state(AdminRouteStates.price)
    except ValueError:
        await message.answer("❌ Введите число (минуты)")
@router.message(AdminRouteStates.price)
async def admin_route_add_price(message: Message, state: FSMContext, session: AsyncSession):
    try:
        price = int(message.text)
        data = await state.get_data()
        await session.execute(
<<<<<<< HEAD
            text("INSERT INTO routes (city_id, name, description, route_type, distance, estimated_duration, price) VALUES (:city_id, :name, :description, :route_type, :distance, :duration, :price)"),
=======
            text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            {
                "city_id": data['city_id'],
                "name": data['name'],
                "description": data['description'],
                "route_type": data['route_type'],
                "distance": data['distance'],
                "duration": data['estimated_duration'],
                "price": price
            }
        )
        await session.commit()
        await message.answer(
            f"✅ Маршрут <b>{data['name']}</b> добавлен!\n\n"
            f"Теперь добавьте точки для этого маршрута через /admin",
            parse_mode="HTML"
        )
        await state.clear()
    except ValueError:
        await message.answer("❌ Введите число (рубли)")
@router.callback_query(F.data.startswith("admin:point:add:"))
async def admin_point_add_start(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT COALESCE(MAX(`order`), 0) + 1 FROM points WHERE route_id = :route_id"),
        {"route_id": route_id}
    )
    next_order = result.scalar()
    await state.update_data(route_id=route_id, order=next_order)
    await callback.message.answer(
        f"📍 <b>Добавление точки</b>\n\n"
        f"Порядковый номер: {next_order}\n\n"
        f"Введите название точки:",
        parse_mode="HTML"
    )
    await state.set_state(AdminPointStates.name)
    await callback.answer()
@router.message(AdminPointStates.name)
async def admin_point_add_name(message: Message, state: FSMContext):
    await state.update_data(name=message.text)
    await message.answer(
        f"Название: <b>{message.text}</b>\n\n"
        "Введите описание точки:",
        parse_mode="HTML"
    )
    await message.answer(
        "Введите текст задания для пользователя:"
    )
    await state.set_state(AdminPointStates.task_text)
@router.message(AdminPointStates.task_text)
async def admin_point_add_task(message: Message, state: FSMContext):
    await state.update_data(task_text=message.text)
    await message.answer(
        "Введите интересный факт о месте (или /skip):"
    )
    await state.set_state(AdminPointStates.fact_text)
@router.message(AdminPointStates.fact_text)
async def admin_point_add_fact(message: Message, state: FSMContext):
    fact = None if message.text == "/skip" else message.text
    await state.update_data(fact_text=fact)
    await message.answer(
<<<<<<< HEAD
        "Минимум людей на фото (число). Введите число или /skip для 1:"
    )
    await state.set_state(AdminPointStates.min_people)
@router.message(AdminPointStates.min_people)
async def admin_point_add_min_people(message: Message, state: FSMContext):
    min_people = 1
    if message.text != "/skip":
        try:
            min_people = max(1, int(message.text))
        except ValueError:
            await message.answer("❌ Введите число от 1 или /skip")
            return
    await state.update_data(min_people=min_people)
    data = await state.get_data()
    try:
        await session.execute(
            text("""
                INSERT INTO points (route_id, name, task_text, fact_text, `order`, min_people)
                VALUES (:route_id, :name, :task_text, :fact_text, :order, :min_people)
            """),
=======
        "Требуется ли поза?\n\n"
        "1 - Руки вверх (hands_up)\n"
        "2 - Сердечко (heart)\n"
        "3 - Указать на объект (point)\n"
        "4 - Не требуется\n\n"
        "Введите номер:"
    )
    await state.set_state(AdminPointStates.require_pose)
@router.message(AdminPointStates.require_pose)
async def admin_point_add_pose(message: Message, state: FSMContext):
    poses = {"1": "hands_up", "2": "heart", "3": "point", "4": None}
    pose = poses.get(message.text)
    if message.text not in poses:
        await message.answer("❌ Введите номер от 1 до 4")
        return
    await state.update_data(require_pose=pose, min_people=1)
    data = await state.get_data()
    try:
        await session.execute(
            text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            {
                "route_id": data['route_id'],
                "name": data['name'],
                "task_text": data['task_text'],
                "fact_text": data.get('fact_text'),
                "order": data['order'],
<<<<<<< HEAD
                "min_people": data.get('min_people', 1)
=======
                "require_pose": data.get('require_pose'),
                "min_people": 1
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            }
        )
        await session.commit()
        await message.answer(
            f"✅ Точка <b>{data['name']}</b> добавлена!\n\n"
            f"Теперь загрузите эталонные фото для проверки локации.\n"
            f"Отправьте фото или используйте /admin для возврата в меню.",
            parse_mode="HTML"
        )
        await state.clear()
    except Exception as e:
        await message.answer(f"❌ Ошибка: {str(e)}")
@router.callback_query(F.data == "admin:stats")
async def admin_statistics(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    stats = await get_detailed_stats(session)
    msg_text = (
        f"📊 <b>Статистика QuestGuideRF</b>\n\n"
        f"👥 <b>Пользователи:</b>\n"
        f"   Всего: {stats['total_users']}\n"
        f"   Активных (7 дней): {stats['active_users']}\n"
        f"   Новых (сегодня): {stats['new_users_today']}\n\n"
        f"🗺 <b>Маршруты:</b>\n"
        f"   Всего: {stats['total_routes']}\n"
        f"   Активных: {stats['active_routes']}\n"
        f"   Пройдено раз: {stats['completed_routes']}\n\n"
        f"📍 <b>Точки:</b>\n"
        f"   Всего: {stats['total_points']}\n\n"
        f"📸 <b>Фото:</b>\n"
        f"   Всего загружено: {stats['total_photos']}\n"
        f"   На проверке: {stats['pending_photos']}\n"
        f"   Принято: {stats['approved_photos']}\n"
        f"   Отклонено: {stats['rejected_photos']}\n\n"
        f"💰 <b>Платежи:</b>\n"
<<<<<<< HEAD
        f"   Всего: {stats['total_payments']} г\n"
=======
        f"   Всего: {stats['total_payments']}₽\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        f"   Успешных: {stats['successful_payments']}\n"
    )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_back_to_menu(),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:photos"))
async def admin_photo_history(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    page = 1
    if "page:" in callback.data:
        try:
            page = int(callback.data.split(":")[-1])
<<<<<<< HEAD
        except Exception as e:
            logger.debug("admin photo history: не удалось распарсить номер страницы %s: %s", callback.data, e)
=======
        except:
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            page = 1
    per_page = 5
    offset = (page - 1) * per_page
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT p.name as point_name, p.order as point_order, r.name as route_name, u.first_name, u.username, up.created_at FROM user_photos up JOIN points p ON up.point_id = p.id JOIN routes r ON p.route_id = r.id JOIN users u ON up.user_id = u.id ORDER BY up.created_at DESC LIMIT :limit OFFSET :offset"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"limit": per_page, "offset": offset}
    )
    photos = result.fetchall()
    result = await session.execute(text("SELECT COUNT(*) FROM user_photos"))
    total = result.scalar()
    total_pages = (total + per_page - 1) // per_page if total > 0 else 1
    msg_text = f"📸 <b>История проверки фото</b>\n\nВсего фото: {total}\nСтраница {page}/{total_pages}\n\n"
    if photos:
        for photo in photos:
            msg_text += (
                f"📷 <b>{photo.point_name}</b> (Маршрут: {photo.route_name})\n"
                f"👤 Пользователь: {photo.first_name} (@{photo.username or 'нет'})\n"
                f"📅 Дата: {photo.created_at.strftime('%d.%m.%Y %H:%M')}\n"
                f"📍 Точка #{photo.point_order}\n\n"
            )
    else:
        msg_text += "Нет фото для отображения."
<<<<<<< HEAD
    await safe_edit_text(
        callback,
=======
    await callback.message.edit_text(
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        msg_text,
        reply_markup=get_photo_history_pagination(page, total_pages),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data == "admin:photos:refresh")
async def admin_photo_history_refresh(callback: CallbackQuery, session: AsyncSession):
    from aiogram.types import CallbackQuery as CallbackQueryType
    fake_callback = CallbackQueryType(
        id=callback.id,
        from_user=callback.from_user,
        chat_instance=callback.chat_instance,
        data="admin:photos",
        message=callback.message
    )
    await admin_photo_history(fake_callback, session)
@router.callback_query(F.data == "admin:settings")
<<<<<<< HEAD
async def admin_settings(callback: CallbackQuery, session: AsyncSession, state: FSMContext = None, skip_answer: bool = False):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    if state:
        await state.clear()
=======
async def admin_settings(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    result = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'restart_notifications_enabled'")
    )
    row = result.fetchone()
    restart_notifications = row[0] == '1' if row else True
    result2 = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
    )
    row2 = result2.fetchone()
    manual_moderation = row2[0] == '1' if row2 else False
    result3 = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'subscription_check_enabled'")
    )
    row3 = result3.fetchone()
    subscription_check = row3[0] == '1' if row3 else (config.channel.require_subscription if hasattr(config.channel, 'require_subscription') else False)
<<<<<<< HEAD
    r_stats = await session.execute(text("SELECT value FROM system_settings WHERE `key` = 'channel_stats_enabled'"))
    row_stats = r_stats.fetchone()
    channel_stats_enabled = row_stats[0] == '1' if row_stats else True
    r_time = await session.execute(text("SELECT value FROM system_settings WHERE `key` = 'channel_stats_time'"))
    row_time = r_time.fetchone()
    channel_stats_time = (row_time[0] or "08:00").strip() if row_time else "08:00"
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    vision_config = config.vision
    msg_text = (
        f"⚙️ <b>Настройки системы</b>\n\n"
        f"📊 <b>Текущие настройки:</b>\n\n"
        f"🎯 Порог уверенности локации: {vision_config.similarity_threshold * 100:.0f}%\n"
        f"📸 Макс. фото в час: Не ограничено\n"
        f"⏱ Время жизни токена: 5 минут\n"
        f"🤖 Автопроверки: {'❌ Выключены' if manual_moderation else '✅ Включены'}\n"
        f"👮 Ручная модерация фото: {'✅ Включена' if manual_moderation else '❌ Выключена'}\n"
        f"📢 Проверка подписки на канал: {'✅ Включена' if subscription_check else '❌ Выключена'}\n"
<<<<<<< HEAD
        f"🔄 Уведомления о перезапуске: {'✅ Включены' if restart_notifications else '❌ Выключены'}\n"
        f"📈 Статистика канала админам: {'✅ Включена' if channel_stats_enabled else '❌ Выключена'} (в {channel_stats_time} МСК)"
=======
        f"🔄 Уведомления о перезапуске: {'✅ Включены' if restart_notifications else '❌ Выключены'}"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(
                text=f"{'🔕 Выключить' if manual_moderation else '🔔 Включить'} ручную модерацию фото",
                callback_data="admin:settings:toggle_manual_moderation"
            )
        ],
        [
            InlineKeyboardButton(
                text=f"{'🔕 Выключить' if subscription_check else '🔔 Включить'} проверку подписки",
                callback_data="admin:settings:toggle_subscription_check"
            )
        ],
        [
            InlineKeyboardButton(
                text=f"{'🔕 Выключить' if restart_notifications else '🔔 Включить'} уведомления о перезапуске",
                callback_data="admin:settings:toggle_restart_notifications"
            )
        ],
        [
            InlineKeyboardButton(
<<<<<<< HEAD
                text=f"{'🔕 Выключить' if channel_stats_enabled else '🔔 Включить'} статистику канала админам",
                callback_data="admin:settings:toggle_channel_stats"
            )
        ],
        [
            InlineKeyboardButton(
                text=f"🕐 Время отправки статистики: {channel_stats_time}",
                callback_data="admin:settings:channel_stats_time"
            )
        ],
        [
            InlineKeyboardButton(
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                text="🛑 Остановить бота",
                callback_data="admin:settings:stop_bot"
            )
        ],
        [
            InlineKeyboardButton(text="« Назад", callback_data="admin:menu")
        ]
    ])
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
<<<<<<< HEAD
    if not skip_answer:
        await callback.answer()
=======
    await callback.answer()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.callback_query(F.data == "admin:settings:toggle_restart_notifications")
async def admin_toggle_restart_notifications(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'restart_notifications_enabled'")
    )
    row = result.fetchone()
    current_value = row[0] if row else '1'
    new_value = '0' if current_value == '1' else '1'
    await session.execute(
<<<<<<< HEAD
        text("INSERT INTO system_settings (`key`, value) VALUES ('restart_notifications_enabled', :value) ON DUPLICATE KEY UPDATE value = :value"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"value": new_value}
    )
    await session.commit()
    status = "включены" if new_value == '1' else "выключены"
    await callback.answer(f"✅ Уведомления о перезапуске {status}")
<<<<<<< HEAD
    await admin_settings(callback, session, skip_answer=True)
=======
    await admin_settings(callback, session)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.callback_query(F.data == "admin:settings:toggle_manual_moderation")
async def admin_toggle_manual_moderation(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
    )
    row = result.fetchone()
    current_value = row[0] if row else '0'
    new_value = '0' if current_value == '1' else '1'
    await session.execute(
<<<<<<< HEAD
        text("INSERT INTO system_settings (`key`, value) VALUES ('manual_photo_moderation_enabled', :value) ON DUPLICATE KEY UPDATE value = :value"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"value": new_value}
    )
    await session.commit()
    status = "включена" if new_value == '1' else "выключена"
    await callback.answer(f"✅ Ручная модерация фото {status}")
<<<<<<< HEAD
    await admin_settings(callback, session, skip_answer=True)
=======
    await admin_settings(callback, session)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.callback_query(F.data == "admin:settings:toggle_subscription_check")
async def admin_toggle_subscription_check(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'subscription_check_enabled'")
    )
    row = result.fetchone()
    if row:
        current_value = row[0]
    else:
        current_value = '1' if config.channel.require_subscription else '0'
    new_value = '0' if current_value == '1' else '1'
    await session.execute(
<<<<<<< HEAD
        text("INSERT INTO system_settings (`key`, value) VALUES ('subscription_check_enabled', :value) ON DUPLICATE KEY UPDATE value = :value"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"value": new_value}
    )
    await session.commit()
    status = "включена" if new_value == '1' else "выключена"
    await callback.answer(f"✅ Проверка подписки {status}")
<<<<<<< HEAD
    await admin_settings(callback, session, skip_answer=True)
@router.callback_query(F.data == "admin:settings:toggle_channel_stats")
async def admin_toggle_channel_stats(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
        text("SELECT value FROM system_settings WHERE `key` = 'channel_stats_enabled'")
    )
    row = result.fetchone()
    current_value = row[0] if row else '1'
    new_value = '0' if current_value == '1' else '1'
    await session.execute(
        text("INSERT INTO system_settings (`key`, value) VALUES ('channel_stats_enabled', :value) ON DUPLICATE KEY UPDATE value = :value"),
        {"value": new_value}
    )
    await session.commit()
    status = "включена" if new_value == '1' else "выключена"
    await callback.answer(f"✅ Отправка статистики канала админам {status}")
    await admin_settings(callback, session, skip_answer=True)
@router.callback_query(F.data == "admin:settings:channel_stats_time")
async def admin_channel_stats_time_start(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await state.set_state(AdminSettingsStates.channel_stats_time)
    await safe_edit_text(
        callback,
        "🕐 <b>Время отправки статистики канала</b>\n\n"
        "Введите время в формате <b>ЧЧ:ММ</b> (по Москве), например <code>08:00</code> или <code>9:30</code>.",
        reply_markup=InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="❌ Отмена", callback_data="admin:settings")]
        ])
    )
    await callback.answer()
@router.message(AdminSettingsStates.channel_stats_time, F.text)
async def admin_channel_stats_time_input(message: Message, session: AsyncSession, state: FSMContext):
    if not is_admin(message.from_user.id):
        await state.clear()
        return
    import re
    input_text = (message.text or "").strip()
    m = re.match(r"^(\d{1,2}):(\d{2})$", input_text)
    if not m:
        await message.answer("Используйте формат ЧЧ:ММ, например 08:00")
        return
    h, mi = int(m.group(1)), int(m.group(2))
    if h < 0 or h > 23 or mi < 0 or mi > 59:
        await message.answer("Часы 0–23, минуты 0–59.")
        return
    time_str = f"{h:02d}:{mi:02d}"
    await session.execute(
        text("INSERT INTO system_settings (`key`, value) VALUES ('channel_stats_time', :value) ON DUPLICATE KEY UPDATE value = :value"),
        {"value": time_str}
    )
    await session.commit()
    await state.clear()
    from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
    await message.answer(
        f"✅ Время отправки статистики сохранено: {time_str} МСК.",
        reply_markup=InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="⚙️ В настройки", callback_data="admin:settings")]
        ])
    )
=======
    await admin_settings(callback, session)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.callback_query(F.data == "admin:settings:stop_bot")
async def admin_stop_bot(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    msg_text = (
        "🛑 <b>Остановка бота</b>\n\n"
        "⚠️ Вы уверены, что хотите остановить бота?"
    )
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(
                text="✅ Да, остановить",
                callback_data="admin:settings:stop_bot:confirm"
            ),
            InlineKeyboardButton(
                text="❌ Отмена",
                callback_data="admin:settings"
            )
        ]
    ])
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    await callback.answer()
@router.callback_query(F.data == "admin:settings:stop_bot:confirm")
async def admin_stop_bot_confirm(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await safe_edit_text(
        callback,
        "🛑 <b>Остановка бота...</b>\n\n"
        "Бот будет остановлен через несколько секунд.",
        reply_markup=None
    )
    await callback.answer("✅ Бот остановлен")
    async def stop_bot_task():
        try:
            admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
            await admin_notifier.notify_bot_stopped(callback.from_user.id, callback.from_user.username)
        except Exception as e:
            logger.error(f"Ошибка отправки уведомления об остановке: {e}")
        import asyncio
        await asyncio.sleep(1)
        logger.info(f"Админ {callback.from_user.id} остановил бота через админ-панель")
        await dp.stop_polling()
    import asyncio
    asyncio.create_task(stop_bot_task())
<<<<<<< HEAD
@router.callback_query(F.data == "admin:referral")
async def admin_referral_menu(callback: CallbackQuery, session: AsyncSession, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await state.clear()
    result = await session.execute(
        text("SELECT value FROM platform_settings WHERE `key` = 'referral_reward_amount'")
    )
    row = result.fetchone()
    current = int(row[0]) if row and row[0] is not None else 10
    msg_text = (
        "🤝 <b>Партнерка (реферальная программа)</b>\n\n"
        f"Гроши за одну покупку приглашённого: <b>{current}</b>\n\n"
        "Измените сумму вознаграждения при необходимости."
    )
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Изменить сумму вознаграждения", callback_data="admin:referral:set_reward")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:menu")],
    ])
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    await callback.answer()
@router.callback_query(F.data == "admin:referral:set_reward")
async def admin_referral_set_reward_start(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await state.set_state(AdminReferralStates.reward_amount)
    await callback.message.answer(
        "✏️ Введите сумму вознаграждения (гроши) за одну покупку приглашённого (целое число):",
        reply_markup=InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="❌ Отмена", callback_data="admin:referral")],
        ])
    )
    await callback.answer()
@router.message(AdminReferralStates.reward_amount, F.text)
async def admin_referral_reward_amount_input(message: Message, session: AsyncSession, state: FSMContext):
    if not is_admin(message.from_user.id):
        return
    try:
        value = int(message.text.strip())
        if value < 0:
            await message.answer("Введите неотрицательное число.")
            return
    except ValueError:
        await message.answer("Введите целое число (гроши).")
        return
    await session.execute(
        text("""
            INSERT INTO platform_settings (`key`, value) VALUES ('referral_reward_amount', :val)
            ON DUPLICATE KEY UPDATE value = :val
        """),
        {"val": str(value)}
    )
    await session.commit()
    await state.clear()
    await message.answer(f"✅ Сохранено: {value} грошей за покупку приглашённого.")
    from bot.loader import bot
    try:
        await bot.send_message(
            message.chat.id,
            "🤝 Партнерка",
            reply_markup=InlineKeyboardMarkup(inline_keyboard=[
                [InlineKeyboardButton(text="« В админ-меню", callback_data="admin:menu")],
            ])
        )
    except Exception:
        pass
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
@router.callback_query(F.data == "admin:promo_codes")
async def admin_promo_codes_list(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT pc.id, pc.code, pc.discount_type, pc.discount_value, pc.route_id, pc.max_uses, pc.used_count as uses_count, pc.valid_from, pc.valid_until, pc.is_active, r.name as route_name FROM promo_codes pc LEFT JOIN routes r ON pc.route_id = r.id ORDER BY pc.id")
=======
        text()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    )
    promos = result.fetchall()
    if not promos:
        msg_text = "🎫 <b>Промокоды</b>\n\nПромокодов пока нет."
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="➕ Создать промокод", callback_data="admin:promo:add")],
            [InlineKeyboardButton(text="« Назад", callback_data="admin:menu")]
        ])
    else:
        msg_text = "🎫 <b>Промокоды</b>\n\n"
        for promo in promos[:10]:
            status = "✅" if promo.is_active else "❌"
            discount_text = ""
            if promo.discount_type == 'percentage':
                discount_text = f"{promo.discount_value}%"
            elif promo.discount_type == 'fixed':
<<<<<<< HEAD
                discount_text = f"{promo.discount_value} г"
=======
                discount_text = f"{promo.discount_value}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            elif promo.discount_type == 'free_route':
                discount_text = f"Бесплатно ({promo.route_name or 'маршрут'})"
            uses_text = f"{promo.uses_count or 0}"
            if promo.max_uses:
                uses_text += f"/{promo.max_uses}"
            msg_text += (
                f"{status} <b>{promo.code}</b>\n"
                f"Скидка: {discount_text}\n"
                f"Использований: {uses_text}\n\n"
            )
        buttons = []
        for promo in promos[:10]:
            buttons.append([
                InlineKeyboardButton(
                    text=f"{'✅' if promo.is_active else '❌'} {promo.code}",
                    callback_data=f"admin:promo:view:{promo.id}"
                )
            ])
        buttons.append([InlineKeyboardButton(text="➕ Создать промокод", callback_data="admin:promo:add")])
        buttons.append([InlineKeyboardButton(text="« Назад", callback_data="admin:menu")])
        keyboard = InlineKeyboardMarkup(inline_keyboard=buttons)
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    await callback.answer()
@router.callback_query(F.data == "admin:promo:add")
async def admin_promo_add_start(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    await callback.message.answer(
        "🎫 <b>Создание промокода</b>\n\n"
        "Введите код промокода (например: SUMMER2024):",
        parse_mode="HTML"
    )
    await state.set_state(AdminPromoCodeStates.code)
    await callback.answer()
@router.message(AdminPromoCodeStates.code)
async def admin_promo_add_code(message: Message, state: FSMContext, session: AsyncSession):
    code = message.text.strip().upper()
    result = await session.execute(
        text("SELECT id FROM promo_codes WHERE code = :code"),
        {"code": code}
    )
    if result.fetchone():
        await message.answer("❌ Промокод с таким кодом уже существует. Введите другой код:")
        return
    await state.update_data(code=code)
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="📊 Процентная скидка", callback_data="admin:promo:type:percentage")],
        [InlineKeyboardButton(text="💰 Фиксированная сумма", callback_data="admin:promo:type:fixed")],
        [InlineKeyboardButton(text="🆓 Бесплатный маршрут", callback_data="admin:promo:type:free_route")],
    ])
    await message.answer(
        f"Код: <b>{code}</b>\n\n"
        "Выберите тип скидки:",
        reply_markup=keyboard,
        parse_mode="HTML"
    )
@router.callback_query(F.data.startswith("admin:promo:type:"))
async def admin_promo_add_type(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    discount_type = callback.data.split(":")[-1]
    await state.update_data(discount_type=discount_type)
    if discount_type == 'free_route':
        result = await session.execute(
            text("SELECT id, name FROM routes WHERE is_active = 1 ORDER BY name LIMIT 50")
        )
        routes = result.fetchall()
        if not routes:
            await callback.answer("❌ Нет активных маршрутов", show_alert=True)
            await state.clear()
            return
        routes_text = "\n".join([f"{r.id}. {r.name}" for r in routes])
        await callback.message.answer(
            f"Тип: <b>Бесплатный маршрут</b>\n\n"
            f"Доступные маршруты:\n{routes_text}\n\n"
            "Введите ID маршрута:",
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.route_id)
    else:
<<<<<<< HEAD
        suffix = "%" if discount_type == 'percentage' else " г"
=======
        suffix = "%" if discount_type == 'percentage' else "₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        await callback.message.answer(
            f"Тип: <b>{'Процентная скидка' if discount_type == 'percentage' else 'Фиксированная сумма'}</b>\n\n"
            f"Введите значение скидки ({suffix}):",
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.discount_value)
    await callback.answer()
@router.message(AdminPromoCodeStates.discount_value)
async def admin_promo_add_value(message: Message, state: FSMContext):
    try:
        value = float(message.text.replace(',', '.'))
        data = await state.get_data()
        discount_type = data.get('discount_type')
        if discount_type == 'percentage' and (value < 0 or value > 100):
            await message.answer("❌ Процент должен быть от 0 до 100. Введите снова:")
            return
        if discount_type == 'fixed' and value < 0:
            await message.answer("❌ Сумма не может быть отрицательной. Введите снова:")
            return
        await state.update_data(discount_value=value)
        await state.update_data(discount_value=value)
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="🌐 Для всех маршрутов", callback_data="admin:promo:route:all")],
            [InlineKeyboardButton(text="📍 Для конкретного маршрута", callback_data="admin:promo:route:specific")],
        ])
        await message.answer(
<<<<<<< HEAD
            f"Значение: <b>{value}{'%' if discount_type == 'percentage' else ' г'}</b>\n\n"
=======
            f"Значение: <b>{value}{'%' if discount_type == 'percentage' else '₽'}</b>\n\n"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            "Выберите, для какого маршрута промокод:",
            reply_markup=keyboard,
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.route_id)
    except ValueError:
        await message.answer("❌ Неверный формат числа. Введите снова:")
@router.callback_query(F.data.startswith("admin:promo:route:"))
async def admin_promo_add_route_choice(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    route_choice = callback.data.split(":")[-1]
    if route_choice == "all":
        await state.update_data(route_id=None)
        await callback.message.answer(
            "Маршрут: <b>Для всех маршрутов</b>\n\n"
            "Введите максимальное количество использований (или 0 для неограниченного):",
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.max_uses)
    else:
        result = await session.execute(
            text("SELECT id, name FROM routes WHERE is_active = 1 ORDER BY name LIMIT 50")
        )
        routes = result.fetchall()
        if not routes:
            await callback.answer("❌ Нет активных маршрутов", show_alert=True)
            return
        routes_text = "\n".join([f"{r.id}. {r.name}" for r in routes])
        await callback.message.answer(
            f"Доступные маршруты:\n{routes_text}\n\n"
            "Введите ID маршрута:",
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.route_id)
    await callback.answer()
@router.message(AdminPromoCodeStates.route_id)
async def admin_promo_add_route(message: Message, state: FSMContext, session: AsyncSession):
    try:
        route_id = int(message.text)
        result = await session.execute(
            text("SELECT id, name FROM routes WHERE id = :route_id AND is_active = 1"),
            {"route_id": route_id}
        )
        route = result.fetchone()
        if not route:
            await message.answer("❌ Маршрут не найден или неактивен. Введите ID снова:")
            return
        await state.update_data(route_id=route_id, route_name=route.name)
        await message.answer(
            f"Маршрут: <b>{route.name}</b>\n\n"
            "Введите максимальное количество использований (или 0 для неограниченного):",
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.max_uses)
    except ValueError:
        await message.answer("❌ Неверный формат ID. Введите число:")
@router.message(AdminPromoCodeStates.max_uses)
async def admin_promo_add_max_uses(message: Message, state: FSMContext):
    try:
        max_uses = int(message.text)
        if max_uses < 0:
            await message.answer("❌ Количество не может быть отрицательным. Введите снова:")
            return
        await state.update_data(max_uses=max_uses if max_uses > 0 else None)
        keyboard = InlineKeyboardMarkup(inline_keyboard=[
            [InlineKeyboardButton(text="✅ Активен", callback_data="admin:promo:active:1")],
            [InlineKeyboardButton(text="❌ Неактивен", callback_data="admin:promo:active:0")],
        ])
        await message.answer(
            f"Макс. использований: <b>{max_uses if max_uses > 0 else 'Неограниченно'}</b>\n\n"
            "Выберите статус промокода:",
            reply_markup=keyboard,
            parse_mode="HTML"
        )
        await state.set_state(AdminPromoCodeStates.is_active)
    except ValueError:
        await message.answer("❌ Неверный формат. Введите число:")
@router.callback_query(F.data.startswith("admin:promo:active:"))
async def admin_promo_add_active(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    is_active = callback.data.split(":")[-1] == '1'
    await state.update_data(is_active=is_active)
    data = await state.get_data()
    try:
        route_id_value = data.get('route_id')
        if route_id_value is None or route_id_value == 0 or route_id_value == '':
            route_id_value = None
        else:
            route_id_value = int(route_id_value)
        await session.execute(
<<<<<<< HEAD
            text("INSERT INTO promo_codes (code, discount_type, discount_value, route_id, max_uses, is_active) VALUES (:code, :discount_type, :discount_value, :route_id, :max_uses, :is_active)"),
=======
            text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            {
                "code": data['code'],
                "discount_type": data['discount_type'],
                "discount_value": data.get('discount_value'),
                "route_id": route_id_value,
                "max_uses": data.get('max_uses'),
                "is_active": 1 if is_active else 0
            }
        )
        await session.commit()
        discount_text = ""
        if data['discount_type'] == 'percentage':
            discount_text = f"{data['discount_value']}%"
        elif data['discount_type'] == 'fixed':
<<<<<<< HEAD
            discount_text = f"{data['discount_value']} г"
=======
            discount_text = f"{data['discount_value']}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        elif data['discount_type'] == 'free_route':
            discount_text = f"Бесплатно"
        max_uses_text = str(data.get('max_uses') or 'Неограниченно')
        await callback.message.answer(
            f"✅ <b>Промокод создан!</b>\n\n"
            f"Код: <b>{data['code']}</b>\n"
            f"Тип: {data['discount_type']}\n"
            f"Скидка: {discount_text}\n"
            f"Макс. использований: {max_uses_text}\n"
            f"Статус: {'✅ Активен' if is_active else '❌ Неактивен'}",
            parse_mode="HTML"
        )
        await state.clear()
        await callback.answer("✅ Промокод создан!")
    except Exception as e:
        logger.error(f"Ошибка создания промокода: {e}", exc_info=True)
        await callback.answer(f"❌ Ошибка: {str(e)}", show_alert=True)
        await state.clear()
@router.callback_query(F.data.startswith("admin:promo:view:"))
async def admin_promo_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    promo_id = int(callback.data.split(":")[-1])
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT pc.id, pc.code, pc.description, pc.discount_type, pc.discount_value, pc.route_id, pc.max_uses, pc.used_count as uses_count, pc.valid_from, pc.valid_until, pc.is_active, r.name as route_name FROM promo_codes pc LEFT JOIN routes r ON pc.route_id = r.id WHERE pc.id = :promo_id"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"promo_id": promo_id}
    )
    promo = result.fetchone()
    if not promo:
        await callback.answer("❌ Промокод не найден", show_alert=True)
        return
    status = "✅ Активен" if promo.is_active else "❌ Неактивен"
    discount_text = ""
    if promo.discount_type == 'percentage':
        discount_text = f"{promo.discount_value}%"
    elif promo.discount_type == 'fixed':
<<<<<<< HEAD
        discount_text = f"{promo.discount_value} г"
=======
        discount_text = f"{promo.discount_value}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    elif promo.discount_type == 'free_route':
        discount_text = f"Бесплатно ({promo.route_name or 'маршрут'})"
    uses_text = f"{promo.uses_count or 0}"
    if promo.max_uses:
        uses_text += f" / {promo.max_uses}"
    else:
        uses_text += " (неограниченно)"
    valid_text = ""
    if promo.valid_from:
        valid_text += f"С: {promo.valid_from.strftime('%d.%m.%Y %H:%M')}\n"
    if promo.valid_until:
        valid_text += f"До: {promo.valid_until.strftime('%d.%m.%Y %H:%M')}\n"
    if not valid_text:
        valid_text = "Без ограничений по времени"
    msg_text = (
        f"🎫 <b>Промокод: {promo.code}</b>\n\n"
        f"Статус: {status}\n"
        f"Тип: {promo.discount_type}\n"
        f"Скидка: {discount_text}\n"
        f"Использований: {uses_text}\n"
        f"Срок действия: {valid_text}\n"
    )
    if promo.description:
        msg_text += f"\nОписание: {promo.description}"
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Редактировать", callback_data=f"admin:promo:edit:{promo_id}")],
        [InlineKeyboardButton(text="🗑 Удалить", callback_data=f"admin:promo:delete:{promo_id}")],
        [InlineKeyboardButton(text="👁 Показать/скрыть", callback_data=f"admin:promo:toggle:{promo_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data="admin:promo_codes")]
    ])
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    await callback.answer()
@router.callback_query(F.data.startswith("admin:promo:edit:"))
async def admin_promo_edit(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    promo_id = int(callback.data.split(":")[-1])
    result = await session.execute(
<<<<<<< HEAD
        text("SELECT pc.id, pc.code, pc.description, pc.discount_type, pc.discount_value, pc.route_id, pc.max_uses, pc.used_count, pc.valid_from, pc.valid_until, pc.is_active, r.name as route_name FROM promo_codes pc LEFT JOIN routes r ON pc.route_id = r.id WHERE pc.id = :promo_id"),
=======
        text(),
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        {"promo_id": promo_id}
    )
    promo = result.fetchone()
    if not promo:
        await callback.answer("❌ Промокод не найден", show_alert=True)
        return
    status = "✅ Активен" if promo.is_active else "❌ Неактивен"
    discount_text = ""
    if promo.discount_type == 'percentage':
        discount_text = f"{promo.discount_value}%"
    elif promo.discount_type == 'fixed':
<<<<<<< HEAD
        discount_text = f"{promo.discount_value} г"
=======
        discount_text = f"{promo.discount_value}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    elif promo.discount_type == 'free_route':
        discount_text = "Бесплатно"
    uses_text = f"{promo.used_count or 0}"
    if promo.max_uses:
        uses_text += f"/{promo.max_uses}"
    else:
        uses_text += "/∞"
    valid_text = ""
    if promo.valid_from or promo.valid_until:
        if promo.valid_from:
            valid_text += f"С {promo.valid_from.strftime('%d.%m.%Y %H:%M')}"
        if promo.valid_until:
            if valid_text:
                valid_text += " до "
            valid_text += promo.valid_until.strftime('%d.%m.%Y %H:%M')
    else:
        valid_text = "Без ограничений по времени"
    route_text = ""
    if promo.route_id:
        route_result = await session.execute(
            text("SELECT name_ru, name_en FROM routes WHERE id = :route_id"),
            {"route_id": promo.route_id}
        )
        route = route_result.fetchone()
        if route:
            route_text = f"\nМаршрут: {route.name_ru or route.name_en}"
    msg_text = (
        f"✏️ <b>Редактирование промокода: {promo.code}</b>\n\n"
        f"Статус: {status}\n"
        f"Тип: {promo.discount_type}\n"
        f"Скидка: {discount_text}\n"
        f"Использований: {uses_text}\n"
        f"Срок действия: {valid_text}{route_text}\n"
    )
    if promo.description:
        msg_text += f"\nОписание: {promo.description}"
    msg_text += "\n\n⚠️ <b>Внимание:</b> Полное редактирование промокодов доступно только через веб-интерфейс.\nЗдесь вы можете только изменить статус активности."
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(
            text="👁 Активировать/деактивировать",
            callback_data=f"admin:promo:toggle:{promo_id}"
        )],
        [InlineKeyboardButton(text="« Назад", callback_data=f"admin:promo:view:{promo_id}")],
    ])
    await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    await callback.answer()
@router.callback_query(F.data.startswith("admin:promo:toggle:"))
async def admin_promo_toggle(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    promo_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT is_active FROM promo_codes WHERE id = :promo_id"),
        {"promo_id": promo_id}
    )
    promo = result.fetchone()
    if not promo:
        await callback.answer("❌ Промокод не найден", show_alert=True)
        return
    new_status = not promo.is_active
    await session.execute(
        text("UPDATE promo_codes SET is_active = :is_active WHERE id = :promo_id"),
        {"is_active": 1 if new_status else 0, "promo_id": promo_id}
    )
    await session.commit()
    await callback.answer(f"✅ Промокод {'активирован' if new_status else 'деактивирован'}")
    from aiogram.types import CallbackQuery as CallbackQueryType
    fake_callback = CallbackQueryType(
        id=callback.id,
        from_user=callback.from_user,
        chat_instance=callback.chat_instance,
        data=f"admin:promo:view:{promo_id}",
        message=callback.message
    )
    await admin_promo_view(fake_callback, session)
@router.callback_query(F.data.startswith("admin:promo:delete:"))
async def admin_promo_delete(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    promo_id = int(callback.data.split(":")[-1])
    result = await session.execute(
        text("SELECT code FROM promo_codes WHERE id = :promo_id"),
        {"promo_id": promo_id}
    )
    promo = result.fetchone()
    if not promo:
        await callback.answer("❌ Промокод не найден", show_alert=True)
        return
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [
            InlineKeyboardButton(text="✅ Да, удалить", callback_data=f"admin:promo:delete_confirm:{promo_id}"),
            InlineKeyboardButton(text="❌ Отмена", callback_data=f"admin:promo:view:{promo_id}")
        ]
    ])
    await safe_edit_text(
        callback,
        f"🗑 <b>Удаление промокода</b>\n\n"
        f"Вы уверены, что хотите удалить промокод <b>{promo.code}</b>?\n\n"
        f"⚠️ Это действие нельзя отменить!",
        reply_markup=keyboard
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:promo:delete_confirm:"))
async def admin_promo_delete_confirm(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    promo_id = int(callback.data.split(":")[-1])
    try:
        await session.execute(
            text("DELETE FROM promo_codes WHERE id = :promo_id"),
            {"promo_id": promo_id}
        )
        await session.commit()
        await callback.answer("✅ Промокод удален")
        from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
        result = await session.execute(
<<<<<<< HEAD
            text("SELECT pc.id, pc.code, pc.discount_type, pc.discount_value, pc.route_id, pc.max_uses, pc.used_count as uses_count, pc.valid_from, pc.valid_until, pc.is_active, r.name as route_name FROM promo_codes pc LEFT JOIN routes r ON pc.route_id = r.id ORDER BY pc.id")
=======
            text()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        )
        promos = result.fetchall()
        if not promos:
            msg_text = "🎫 <b>Промокоды</b>\n\nПромокодов пока нет."
            keyboard = InlineKeyboardMarkup(inline_keyboard=[
                [InlineKeyboardButton(text="➕ Создать промокод", callback_data="admin:promo:add")],
                [InlineKeyboardButton(text="« Назад", callback_data="admin:menu")]
            ])
        else:
            msg_text = "🎫 <b>Промокоды</b>\n\n"
            for promo in promos[:10]:
                status = "✅" if promo.is_active else "❌"
                discount_text = ""
                if promo.discount_type == 'percentage':
                    discount_text = f"{promo.discount_value}%"
                elif promo.discount_type == 'fixed':
<<<<<<< HEAD
                    discount_text = f"{promo.discount_value} г"
=======
                    discount_text = f"{promo.discount_value}₽"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                elif promo.discount_type == 'free_route':
                    discount_text = f"Бесплатно"
                uses_text = f"{promo.uses_count or 0}"
                if promo.max_uses:
                    uses_text += f"/{promo.max_uses}"
                msg_text += (
                    f"{status} <b>{promo.code}</b>\n"
                    f"Скидка: {discount_text}\n"
                    f"Использований: {uses_text}\n\n"
                )
            buttons = []
            for promo in promos[:10]:
                buttons.append([
                    InlineKeyboardButton(
                        text=f"{'✅' if promo.is_active else '❌'} {promo.code}",
                        callback_data=f"admin:promo:view:{promo.id}"
                    )
                ])
            buttons.append([InlineKeyboardButton(text="➕ Создать промокод", callback_data="admin:promo:add")])
            buttons.append([InlineKeyboardButton(text="« Назад", callback_data="admin:menu")])
            keyboard = InlineKeyboardMarkup(inline_keyboard=buttons)
        await safe_edit_text(callback, msg_text, reply_markup=keyboard)
    except Exception as e:
        logger.error(f"Ошибка удаления промокода: {e}", exc_info=True)
        error_msg = str(e)[:50] if len(str(e)) > 50 else str(e)
        await callback.answer(f"❌ Ошибка: {error_msg}", show_alert=True)
async def get_admin_stats(session: AsyncSession) -> dict:
    stats = {}
    result = await session.execute(text("SELECT COUNT(*) FROM users"))
    stats['total_users'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM routes"))
    stats['total_routes'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM points"))
    stats['total_points'] = result.scalar()
    stats['pending_photos'] = 0
    return stats
async def get_detailed_stats(session: AsyncSession) -> dict:
    stats = {}
    result = await session.execute(text("SELECT COUNT(*) FROM users"))
    stats['total_users'] = result.scalar()
    week_ago = datetime.utcnow() - timedelta(days=7)
    result = await session.execute(
        text("SELECT COUNT(DISTINCT user_id) FROM user_progress WHERE updated_at > :week_ago"),
        {"week_ago": week_ago}
    )
    stats['active_users'] = result.scalar()
    today = datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
    result = await session.execute(
        text("SELECT COUNT(*) FROM users WHERE created_at > :today"),
        {"today": today}
    )
    stats['new_users_today'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM routes"))
    stats['total_routes'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM routes WHERE is_active = 1"))
    stats['active_routes'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM user_progress WHERE status = 'completed'"))
    stats['completed_routes'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM points"))
    stats['total_points'] = result.scalar()
    result = await session.execute(text("SELECT COUNT(*) FROM user_photos"))
    stats['total_photos'] = result.scalar()
    stats['pending_photos'] = 0
    stats['approved_photos'] = 0
    stats['rejected_photos'] = 0
    result = await session.execute(text("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'"))
    stats['total_payments'] = result.scalar() or 0
    result = await session.execute(text("SELECT COUNT(*) FROM payments WHERE status = 'completed'"))
    stats['successful_payments'] = result.scalar()
    return stats
@router.callback_query(F.data.startswith("appr:"))
async def admin_approve_photo(
    callback: CallbackQuery,
    session: AsyncSession,
):
    try:
        parts = callback.data.split(":", 3)
        if len(parts) < 4:
            await callback.answer("❌ Неверный формат данных")
            return
        user_telegram_id = int(parts[1])
        point_id = int(parts[2])
        progress_id = int(parts[3])
<<<<<<< HEAD
        try:
            await callback.message.edit_reply_markup(reply_markup=None)
        except Exception:
            pass
        if callback.message and callback.message.photo:
            photo = callback.message.photo[-1]
            photo_file_id = photo.file_id
            dup_check = await session.execute(
                text("SELECT moderation_status FROM user_photos WHERE file_id = :fid AND moderation_status != 'pending' LIMIT 1"),
                {"fid": photo_file_id},
            )
            if dup_check.fetchone():
                await callback.message.edit_caption(
                    caption=f"{callback.message.caption}\n\nℹ️ Фото уже обработано другим модератором",
                    parse_mode="HTML",
                )
                await callback.answer("ℹ️ Фото уже обработано другим модератором", show_alert=True)
                return
        else:
            await callback.answer("❌ Фото не найдено в сообщении")
            return
        logger.info(f"Админ {callback.from_user.id} принимает фото от пользователя {user_telegram_id}")
=======
        logger.info(f"Админ {callback.from_user.id} принимает фото от пользователя {user_telegram_id}")
        if not callback.message or not callback.message.photo:
            await callback.answer("❌ Фото не найдено в сообщении")
            return
        photo = callback.message.photo[-1]
        photo_file_id = photo.file_id
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        progress_repo = ProgressRepository(session)
        point_repo = PointRepository(session)
        progress = await progress_repo.get(progress_id)
        point = await point_repo.get(point_id)
        if not progress or not point:
            await callback.answer("❌ Данные не найдены")
            return
        user_result = await session.execute(
            text("SELECT id, language FROM users WHERE telegram_id = :telegram_id"),
            {"telegram_id": user_telegram_id}
        )
        user_row = user_result.fetchone()
        if not user_row:
            await callback.answer("❌ Пользователь не найден")
            return
        user_id = user_row[0]
<<<<<<< HEAD
        user_language = user_row[1] if len(user_row) > 1 else 'ru'
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        try:
            file = await bot.get_file(photo_file_id)
            temp_path = f"photos/temp_{photo_file_id}.jpg"
            Path("photos").mkdir(exist_ok=True)
            await bot.download_file(file.file_path, temp_path)
            user_photos_dir = Path("../../photos") / str(user_telegram_id)
            user_photos_dir.mkdir(parents=True, exist_ok=True)
            timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
            filename = f"point_{point_id}_{timestamp}.jpg"
            permanent_path = user_photos_dir / filename
            shutil.copy2(temp_path, str(permanent_path))
            os.remove(temp_path)
            relative_path = f"/photos/{user_telegram_id}/{filename}"
            await session.execute(
                text(
<<<<<<< HEAD
                    "INSERT INTO user_photos (user_id, point_id, file_id, file_path, file_hash, moderation_status) "
                    "VALUES (:user_id, :point_id, :file_id, :file_path, :file_hash, 'approved')"
=======
                    "INSERT INTO user_photos (user_id, point_id, file_id, file_path, file_hash) "
                    "VALUES (:user_id, :point_id, :file_id, :file_path, :file_hash)"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                ),
                {
                    "user_id": user_id,
                    "point_id": point_id,
                    "file_id": photo_file_id,
                    "file_path": relative_path,
                    "file_hash": None,
                }
            )
            await session.execute(
                text(
                    "UPDATE user_progress "
                    "SET points_completed = points_completed + 1, "
                    "    current_point_id = :point_id, "
                    "    current_point_order = :point_order, "
                    "    updated_at = NOW() "
                    "WHERE user_id = :user_id AND route_id = :route_id"
                ),
                {
                    "user_id": user_id,
                    "point_id": point_id,
                    "point_order": point.order,
                    "route_id": progress.route_id,
                }
            )
            await session.commit()
            logger.info(f"Фото сохранено: {relative_path}, user_id={user_id}, point_id={point_id}")
        except Exception as e:
            logger.error(f"Ошибка при сохранении фото: {e}", exc_info=True)
            await callback.answer(f"❌ Ошибка сохранения: {str(e)}")
            return
<<<<<<< HEAD
        next_point_data = await point_repo.get_next_point_data(progress.route_id, point.order)
        if next_point_data:
            await progress_repo.complete_point(progress, next_point_data.id, next_point_data.order)
=======
        completed_count = progress.current_point_order + 1
        next_point = await point_repo.get_next_point(progress.route_id, progress.current_point_order)
        if next_point:
            await progress_repo.complete_point(progress, next_point.id, next_point.order)
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        else:
            await progress_repo.complete_point(progress, None, None)
        await session.commit()
        try:
            from bot.utils.i18n import i18n, get_localized_field
<<<<<<< HEAD
            from bot.utils.helpers import parse_task_text, split_long_message, get_point_tasks, tasks_from_models
            from bot.repositories.task import TaskRepository
            await bot.send_message(
                user_telegram_id,
                i18n.get('admin_approved_photo_short', user_language, default='Админ принял ваше фото.\n\n✅ Отлично! Точка засчитана!'),
                parse_mode="HTML",
            )
            if next_point_data:
                next_point_full = await point_repo.get_with_tasks(next_point_data.id)
                if next_point_full:
                    tasks = get_point_tasks(next_point_full)
                    if not tasks:
                        task_repo = TaskRepository(session)
                        task_models = await task_repo.get_by_point(next_point_full.id)
                        tasks = tasks_from_models(task_models)
                    if tasks:
                        first_task = tasks[0]
                        task_text_value = first_task.get('task_text_en') if user_language == 'en' and first_task.get('task_text_en') else first_task.get('task_text', '')
                        parsed = parse_task_text(task_text_value)
                        next_point_name = get_localized_field(next_point_full, 'name', user_language)
                        header = f"📍 {i18n.get('next_point', user_language, default='Следующая точка')}: {next_point_full.order}. {next_point_name}\n\n"
                        messages = []
                        if parsed['directions']:
                            messages.append(header + parsed['directions'].strip())
                            header = ""
                        audio_text = get_localized_field(next_point_full, 'audio_text', user_language)
                        if audio_text:
                            messages.append((header + audio_text.strip()) if header else audio_text.strip())
                            header = ""
                        from aiogram.utils.keyboard import InlineKeyboardBuilder
                        from aiogram.types import InlineKeyboardButton
                        kb = InlineKeyboardBuilder()
                        kb.row(InlineKeyboardButton(
                            text=i18n.get("i_am_here", user_language, default="📍 Я на месте"),
                            callback_data=f"i_am_here:{next_point_full.id}:0",
                        ))
                        kb.row(InlineKeyboardButton(
                            text=i18n.get("cancel_quest", user_language, default="❌ Выйти из квеста"),
                            callback_data=f"cancel_quest:{progress.route_id}",
                        ))
                        for i, msg in enumerate(messages):
                            parts_list = split_long_message(msg)
                            is_last_msg = (i == len(messages) - 1)
                            for j, part in enumerate(parts_list):
                                is_last_part = (j == len(parts_list) - 1)
                                if is_last_msg and is_last_part:
                                    await bot.send_message(user_telegram_id, part, reply_markup=kb.as_markup())
                                else:
                                    await bot.send_message(user_telegram_id, part)
                        if not messages:
                            await bot.send_message(user_telegram_id, header.strip(), reply_markup=kb.as_markup())
            else:
                from bot.keyboards.user import UserKeyboards
                completion_msg = f"🎉 <b>{i18n.get('quest_completed', user_language)}!</b>\n\n{i18n.get('quiz_menu_description', user_language)}"
                await bot.send_message(
                    user_telegram_id,
                    completion_msg,
                    reply_markup=UserKeyboards.quest_completed(progress.id, user_language),
                    parse_mode="HTML",
                )
                from bot.utils.commands import set_user_commands
                await set_user_commands(bot, user_telegram_id, user_language, in_quest=False)
        except Exception as e:
            logger.error(f"Ошибка отправки уведомления пользователю: {e}", exc_info=True)
        await callback.message.edit_caption(
            caption=f"{callback.message.caption}\n\n✅ <b>ПРИНЯТО</b> модератором @{callback.from_user.username or callback.from_user.id}",
            parse_mode="HTML",
        )
        await callback.answer("✅ Фото принято!")
=======
            from aiogram.types import Message as FakeMessage
            user_language = user_row[1] if user_row and len(user_row) > 1 else 'ru'
            result_mod = await session.execute(
                text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
            )
            row_mod = result_mod.fetchone()
            is_manual = row_mod[0] == '1' if row_mod else False
            if next_point:
                point_name = get_localized_field(point, 'name', user_language)
                next_point_name = get_localized_field(next_point, 'name', user_language)
                next_point_task = get_localized_field(next_point, 'task_text', user_language)
                if is_manual:
                    await bot.send_message(
                        user_telegram_id,
                        f"✅ {i18n.get('point_completed', user_language)}: {point_name}\n\n"
                        f"{i18n.get('next_point', user_language)}: {next_point_name}\n"
                        f"📝 {next_point_task}",
                        parse_mode="HTML"
                    )
                else:
                    await bot.send_message(
                        user_telegram_id,
                        f"✅ <b>{i18n.get('admin_approved_photo', user_language)}</b>\n\n"
                        f"📍 {i18n.get('point_completed', user_language)}: {point_name}\n\n"
                        f"{i18n.get('next_point', user_language)}: {next_point_name}\n"
                        f"📝 {next_point_task}",
                        parse_mode="HTML"
                    )
            else:
                completion_msg = f"🎉 <b>{i18n.get('quest_completed', user_language)}!</b>\n\n"
                if is_manual:
                    await bot.send_message(
                        user_telegram_id,
                        f"✅ {i18n.get('point_completed', user_language)}!\n\n" + completion_msg,
                        parse_mode="HTML"
                    )
                else:
                    await bot.send_message(
                        user_telegram_id,
                        f"✅ <b>{i18n.get('admin_approved_photo', user_language)}</b>\n\n" + completion_msg,
                        parse_mode="HTML"
                    )
        except Exception as e:
            logger.error(f"Ошибка отправки уведомления пользователю: {e}")
        await callback.message.edit_caption(
            caption=f"{callback.message.caption}\n\n✅ <b>ПРИНЯТО</b> администратором @{callback.from_user.username or callback.from_user.id}",
            parse_mode="HTML"
        )
        await callback.answer("✅ Фото принято! Пользователь уведомлён")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    except Exception as e:
        logger.error(f"Ошибка при принятии фото: {e}", exc_info=True)
        await callback.answer("❌ Ошибка при обработке")
@router.callback_query(F.data.startswith("rej:"))
async def admin_reject_photo(
    callback: CallbackQuery,
    session: AsyncSession,
):
    try:
        parts = callback.data.split(":", 1)
        if len(parts) < 2:
            await callback.answer("❌ Неверный формат данных")
            return
        user_telegram_id = int(parts[1])
<<<<<<< HEAD
        try:
            await callback.message.edit_reply_markup(reply_markup=None)
        except Exception:
            pass
        if callback.message and callback.message.photo:
            photo_file_id = callback.message.photo[-1].file_id
            dup_check = await session.execute(
                text("SELECT moderation_status FROM user_photos WHERE file_id = :fid AND moderation_status != 'pending' LIMIT 1"),
                {"fid": photo_file_id},
            )
            if dup_check.fetchone():
                await callback.message.edit_caption(
                    caption=f"{callback.message.caption}\n\nℹ️ Фото уже обработано другим модератором",
                    parse_mode="HTML",
                )
                await callback.answer("ℹ️ Фото уже обработано", show_alert=True)
                return
            await session.execute(
                text("UPDATE user_photos SET moderation_status = 'rejected' WHERE file_id = :fid"),
                {"fid": photo_file_id},
            )
            await session.commit()
=======
        result_mod = await session.execute(
            text("SELECT value FROM system_settings WHERE `key` = 'manual_photo_moderation_enabled'")
        )
        row_mod = result_mod.fetchone()
        is_manual = row_mod[0] == '1' if row_mod else False
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        user_result = await session.execute(
            text("SELECT language FROM users WHERE telegram_id = :telegram_id"),
            {"telegram_id": user_telegram_id}
        )
        user_row = user_result.fetchone()
        user_language = user_row[0] if user_row else 'ru'
        from bot.utils.i18n import i18n
        try:
<<<<<<< HEAD
            await bot.send_message(
                user_telegram_id,
                f"❌ <b>{i18n.get('admin_photo_rejected', user_language, default='Администратор отклонил ваше фото')}</b>\n\n"
                f"{i18n.get('photo_rejected_try_again', user_language, default='Пожалуйста, попробуйте сделать новое фото и отправьте его снова.')}\n\n"
                f"💡 {i18n.get('photo_tips', user_language, default='Убедитесь, что:')}\n"
                f"• {i18n.get('photo_tip_location', user_language, default='Вы находитесь в нужном месте')}\n"
                f"• {i18n.get('photo_tip_elements', user_language, default='На фото видны все необходимые элементы')}\n"
                f"• {i18n.get('photo_tip_quality', user_language, default='Фото сделано чётко и ясно')}",
                parse_mode="HTML"
            )
        except Exception as e:
            logger.error(f"Ошибка отправки уведомления пользователю: {e}")
        await callback.message.edit_caption(
            caption=f"{callback.message.caption}\n\n❌ <b>ОТКЛОНЕНО</b> модератором @{callback.from_user.username or callback.from_user.id}",
            parse_mode="HTML"
        )
        try:
            await callback.answer("❌ Фото отклонено")
        except Exception:
            pass
=======
            if is_manual:
                await bot.send_message(
                    user_telegram_id,
                    f"❌ {i18n.get('photo_rejected', user_language, default='Фото не прошло проверку')}\n\n"
                    f"{i18n.get('photo_rejected_try_again', user_language, default='Пожалуйста, попробуйте сделать новое фото и отправьте его снова.')}\n\n"
                    f"💡 {i18n.get('photo_tips', user_language, default='Убедитесь, что:')}\n"
                    f"• {i18n.get('photo_tip_location', user_language, default='Вы находитесь в нужном месте')}\n"
                    f"• {i18n.get('photo_tip_elements', user_language, default='На фото видны все необходимые элементы')}\n"
                    f"• {i18n.get('photo_tip_quality', user_language, default='Фото сделано чётко и ясно')}",
                    parse_mode="HTML"
                )
            else:
                await bot.send_message(
                    user_telegram_id,
                    f"❌ <b>{i18n.get('admin_photo_rejected', user_language, default='Администратор отклонил ваше фото')}</b>\n\n"
                    f"{i18n.get('photo_rejected_try_again', user_language, default='Пожалуйста, попробуйте сделать новое фото и отправьте его снова.')}\n\n"
                    f"💡 {i18n.get('photo_tips', user_language, default='Убедитесь, что:')}\n"
                    f"• {i18n.get('photo_tip_location', user_language, default='Вы находитесь в нужном месте')}\n"
                    f"• {i18n.get('photo_tip_elements', user_language, default='На фото видны все необходимые элементы')}\n"
                    f"• {i18n.get('photo_tip_quality', user_language, default='Фото сделано чётко и ясно')}",
                    parse_mode="HTML"
                )
        except Exception as e:
            logger.error(f"Ошибка отправки уведомления пользователю: {e}")
        await callback.message.edit_caption(
            caption=f"{callback.message.caption}\n\n❌ <b>ОТКЛОНЕНО</b> администратором @{callback.from_user.username or callback.from_user.id}",
            parse_mode="HTML"
        )
        try:
            await callback.answer("❌ Фото отклонено. Пользователь уведомлён")
        except Exception as answer_error:
            if "query is too old" not in str(answer_error).lower() and "query id is invalid" not in str(answer_error).lower():
                logger.error(f"Ошибка при ответе на callback: {answer_error}")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        logger.info(f"Админ {callback.from_user.id} отклонил фото от пользователя {user_telegram_id}")
    except Exception as e:
        logger.error(f"Ошибка при отклонении фото: {e}", exc_info=True)
        try:
            await callback.answer("❌ Ошибка при обработке")
<<<<<<< HEAD
        except Exception:
            pass
from bot.fsm.admin_states import AdminModeratorStates
@router.callback_query(F.data.startswith("admin:mod_request:approve:"))
async def admin_approve_mod_request(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав", show_alert=True)
        return
    parts = callback.data.split(":")
    user_id = int(parts[3])
    request_id = int(parts[4])
    try:
        from sqlalchemy import select
        from bot.models.user import User
        admin_result = await session.execute(
            select(User.id).where(User.telegram_id == callback.from_user.id)
        )
        row = admin_result.first()
        admin_user_id = int(row[0]) if row else None
        if admin_user_id is None:
            await callback.answer("❌ Ваш аккаунт не найден в БД (напишите боту /start)", show_alert=True)
            return
        await session.execute(
            text("UPDATE moderator_requests SET status = 'approved', reviewed_by = :admin_id, reviewed_at = NOW() WHERE id = :request_id"),
            {"admin_id": admin_user_id, "request_id": request_id}
        )
        await session.execute(
            text("UPDATE users SET role = 'MODERATOR' WHERE id = :user_id"),
            {"user_id": user_id}
        )
        await session.commit()
        result = await session.execute(
            text("SELECT telegram_id FROM users WHERE id = :user_id"),
            {"user_id": user_id}
        )
        row = result.fetchone()
        if row:
            try:
                await callback.bot.send_message(
                    row[0],
                    "🎉 <b>Поздравляем!</b>\n\n"
                    "Ваша заявка на модератора одобрена!\n\n"
                    "Теперь вы можете:\n"
                    "• Создавать маршруты\n"
                    "• Управлять точками\n"
                    "• Получать доход от прохождений\n\n"
                    "Используйте /moderator для входа в кабинет создателя.",
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Не удалось уведомить пользователя: {e}")
        await callback.message.edit_text(
            callback.message.text + "\n\n✅ <b>ОДОБРЕНО</b>",
            parse_mode="HTML"
        )
        await callback.answer("✅ Заявка одобрена!")
    except Exception as e:
        logger.error(f"Ошибка при одобрении заявки: {e}")
        await callback.answer("❌ Ошибка", show_alert=True)
@router.callback_query(F.data.startswith("admin:mod_request:reject:"))
async def admin_reject_mod_request(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав", show_alert=True)
        return
    parts = callback.data.split(":")
    user_id = int(parts[3])
    request_id = int(parts[4])
    try:
        from sqlalchemy import select
        from bot.models.user import User
        admin_result = await session.execute(
            select(User.id).where(User.telegram_id == callback.from_user.id)
        )
        row = admin_result.first()
        admin_user_id = int(row[0]) if row else None
        if admin_user_id is None:
            await callback.answer("❌ Ваш аккаунт не найден в БД (напишите боту /start)", show_alert=True)
            return
        await session.execute(
            text("UPDATE moderator_requests SET status = 'rejected', reviewed_by = :admin_id, reviewed_at = NOW() WHERE id = :request_id"),
            {"admin_id": admin_user_id, "request_id": request_id}
        )
        await session.commit()
        result = await session.execute(
            text("SELECT telegram_id FROM users WHERE id = :user_id"),
            {"user_id": user_id}
        )
        row = result.fetchone()
        if row:
            try:
                await callback.bot.send_message(
                    row[0],
                    "😔 <b>К сожалению, ваша заявка на модератора отклонена.</b>\n\n"
                    "Вы можете подать заявку повторно позже, более подробно описав свой опыт и планы.",
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Не удалось уведомить пользователя: {e}")
        await callback.message.edit_text(
            callback.message.text + "\n\n❌ <b>ОТКЛОНЕНО</b>",
            parse_mode="HTML"
        )
        await callback.answer("❌ Заявка отклонена")
    except Exception as e:
        logger.error(f"Ошибка при отклонении заявки: {e}")
        await callback.answer("❌ Ошибка", show_alert=True)
@router.callback_query(F.data.startswith("admin:route_mod:approve:"))
async def admin_approve_route(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав", show_alert=True)
        return
    parts = callback.data.split(":")
    route_id = int(parts[3])
    moderator_id = int(parts[4])
    try:
        await session.execute(
            text("UPDATE routes SET is_active = 1 WHERE id = :route_id"),
            {"route_id": route_id}
        )
        await session.commit()
        result = await session.execute(
            text("SELECT r.name, u.telegram_id FROM routes r JOIN users u ON r.creator_id = u.id WHERE r.id = :route_id"),
            {"route_id": route_id}
        )
        row = result.fetchone()
        if row:
            try:
                await callback.bot.send_message(
                    row[1],
                    f"✅ <b>Ваш маршрут одобрен!</b>\n\n"
                    f"📍 Маршрут: {row[0]}\n\n"
                    f"Теперь маршрут активен и доступен пользователям.",
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Не удалось уведомить модератора: {e}")
        await callback.message.edit_text(
            callback.message.text + "\n\n✅ <b>МАРШРУТ ОДОБРЕН</b>",
            parse_mode="HTML"
        )
        await callback.answer("✅ Маршрут одобрен!")
    except Exception as e:
        logger.error(f"Ошибка при одобрении маршрута: {e}")
        await callback.answer("❌ Ошибка", show_alert=True)
@router.callback_query(F.data.startswith("admin:route_mod:reject:"))
async def admin_reject_route(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав", show_alert=True)
        return
    parts = callback.data.split(":")
    route_id = int(parts[3])
    moderator_id = int(parts[4])
    try:
        result = await session.execute(
            text("SELECT r.name, u.telegram_id FROM routes r JOIN users u ON r.creator_id = u.id WHERE r.id = :route_id"),
            {"route_id": route_id}
        )
        row = result.fetchone()
        if row:
            try:
                await callback.bot.send_message(
                    row[1],
                    f"❌ <b>Ваш маршрут отклонён</b>\n\n"
                    f"📍 Маршрут: {row[0]}\n\n"
                    f"Пожалуйста, проверьте содержимое и попробуйте снова или свяжитесь с администрацией.",
                    parse_mode="HTML"
                )
            except Exception as e:
                logger.error(f"Не удалось уведомить модератора: {e}")
        await callback.message.edit_text(
            callback.message.text + "\n\n❌ <b>МАРШРУТ ОТКЛОНЁН</b>",
            parse_mode="HTML"
        )
        await callback.answer("❌ Маршрут отклонён")
    except Exception as e:
        logger.error(f"Ошибка при отклонении маршрута: {e}")
        await callback.answer("❌ Ошибка", show_alert=True)
@router.callback_query(F.data.startswith("admin:reply_mod:"))
async def admin_reply_to_mod(callback: CallbackQuery, state: FSMContext):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав", show_alert=True)
        return
    moderator_telegram_id = int(callback.data.split(":")[2])
    await state.update_data(reply_to_mod_id=moderator_telegram_id)
    await state.set_state(AdminModeratorStates.reply_message)
    await callback.message.answer(
        "✉️ <b>Ответ модератору</b>\n\n"
        "Введите сообщение для отправки:",
        parse_mode="HTML"
    )
    await callback.answer()
@router.message(AdminModeratorStates.reply_message)
async def admin_send_reply_to_mod(message: Message, state: FSMContext):
    if not is_admin(message.from_user.id):
        return
    data = await state.get_data()
    moderator_telegram_id = data.get('reply_to_mod_id')
    if not moderator_telegram_id:
        await message.answer("❌ Ошибка: не найден получатель")
        await state.clear()
        return
    try:
        await message.bot.send_message(
            moderator_telegram_id,
            f"📩 <b>Ответ от администрации</b>\n\n"
            f"{message.text}",
            parse_mode="HTML"
        )
        await message.answer("✅ Сообщение отправлено модератору!")
    except Exception as e:
        logger.error(f"Ошибка отправки сообщения модератору: {e}")
        await message.answer("❌ Не удалось отправить сообщение")
    await state.clear()
=======
        except Exception as answer_error:
            if "query is too old" not in str(answer_error).lower() and "query id is invalid" not in str(answer_error).lower():
                logger.error(f"Ошибка при ответе на callback: {answer_error}")
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
