import logging
from aiogram import Router, F
from aiogram.types import CallbackQuery
from aiogram.fsm.context import FSMContext
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from bot.repositories.tag import TagRepository
from bot.repositories.route import RouteRepository
from bot.keyboards.filters import FilterKeyboards
from bot.keyboards.user import UserKeyboards
from bot.utils.safe_edit import safe_edit_text
logger = logging.getLogger(__name__)
router = Router()
user_filters = {}
@router.callback_query(F.data.startswith("filter:menu:"))
async def show_filter_menu(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    await safe_edit_text(
        callback,
        "🔍 <b>Фильтры маршрутов</b>\n\n"
        "Выберите, по каким параметрам отфильтровать маршруты:",
        reply_markup=FilterKeyboards.get_filter_menu(city_id)
    )
@router.callback_query(F.data.startswith("filter:topics:"))
async def show_topic_filters(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type("topic")
    selected = user_filters.get(user_id, {}).get("topics", set())
    await safe_edit_text(
        callback,
        "🎨 <b>Фильтр по темам</b>\n\n"
        "Выберите интересующие темы (можно несколько):",
        reply_markup=FilterKeyboards.get_topic_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '📌'} for t in tags
        ], selected)
    )
@router.callback_query(F.data.startswith("filter:age:"))
async def show_age_filters(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type("age")
    selected = user_filters.get(user_id, {}).get("age", set())
    await safe_edit_text(
        callback,
        "👨‍👩‍👧 <b>Фильтр по возрасту</b>\n\n"
        "Выберите подходящую категорию:",
        reply_markup=FilterKeyboards.get_age_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '👤'} for t in tags
        ], selected)
    )
@router.callback_query(F.data.startswith("filter:difficulty:"))
async def show_difficulty_filters(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type("difficulty")
    selected = user_filters.get(user_id, {}).get("difficulty", set())
    await safe_edit_text(
        callback,
        "⭐ <b>Фильтр по сложности</b>\n\n"
        "Выберите уровень сложности:",
        reply_markup=FilterKeyboards.get_difficulty_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '⭐'} for t in tags
        ], selected)
    )
@router.callback_query(F.data.startswith("filter:duration:"))
async def show_duration_filters(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type("duration")
    selected = user_filters.get(user_id, {}).get("duration", set())
    await safe_edit_text(
        callback,
        "⏱️ <b>Фильтр по длительности</b>\n\n"
        "Выберите желаемую длительность:",
        reply_markup=FilterKeyboards.get_duration_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '⏰'} for t in tags
        ], selected)
    )
@router.callback_query(F.data.startswith("filter:season:"))
async def show_season_filters(callback: CallbackQuery, session: AsyncSession):
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type("season")
    selected = user_filters.get(user_id, {}).get("season", set())
    await safe_edit_text(
        callback,
        "🌦️ <b>Фильтр по сезону</b>\n\n"
        "Выберите подходящий сезон:",
        reply_markup=FilterKeyboards.get_season_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '🌍'} for t in tags
        ], selected)
    )
@router.callback_query(F.data.startswith("filter:toggle:"))
async def toggle_filter(callback: CallbackQuery, session: AsyncSession):
    parts = callback.data.split(":")
    filter_type = parts[2]
    tag_id = int(parts[3])
    city_id = int(parts[4])
    user_id = callback.from_user.id
    if user_id not in user_filters:
        user_filters[user_id] = {}
    if filter_type not in user_filters[user_id]:
        user_filters[user_id][filter_type] = set()
    if tag_id in user_filters[user_id][filter_type]:
        user_filters[user_id][filter_type].remove(tag_id)
    else:
        user_filters[user_id][filter_type].add(tag_id)
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type(filter_type)
    keyboards = {
        'topic': FilterKeyboards.get_topic_filters,
        'difficulty': FilterKeyboards.get_difficulty_filters,
        'duration': FilterKeyboards.get_duration_filters,
        'season': FilterKeyboards.get_season_filters
    }
    await callback.message.edit_reply_markup(
        reply_markup=keyboards[filter_type](city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '📌'} for t in tags
        ], user_filters[user_id][filter_type])
    )
@router.callback_query(F.data.startswith("filter:select:"))
async def select_filter(callback: CallbackQuery, session: AsyncSession):
    parts = callback.data.split(":")
    filter_type = parts[2]
    tag_id = int(parts[3])
    city_id = int(parts[4])
    user_id = callback.from_user.id
    if user_id not in user_filters:
        user_filters[user_id] = {}
    user_filters[user_id][filter_type] = {tag_id}
    tag_repo = TagRepository(session)
    tags = await tag_repo.get_by_type(filter_type)
    await callback.message.edit_reply_markup(
        reply_markup=FilterKeyboards.get_age_filters(city_id, [
            {'id': t.id, 'name': t.name, 'icon': t.icon or '👤'} for t in tags
        ], user_filters[user_id][filter_type])
    )
@router.callback_query(F.data.startswith("filter:apply:"))
async def apply_filters(callback: CallbackQuery, session: AsyncSession, user: User):
    from bot.utils.i18n import i18n, get_localized_field
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    filters = user_filters.get(user_id, {})
    route_repo = RouteRepository(session)
    routes = await route_repo.get_routes_by_filters(city_id, filters)
    if not routes:
        await callback.answer(i18n.get("no_routes_found", user.language, default="No routes found with these filters"), show_alert=True)
        return
    active_filters = []
    tag_repo = TagRepository(session)
    for filter_type, tag_ids in filters.items():
        if tag_ids:
            for tag_id in tag_ids:
                tag = await tag_repo.get(tag_id)
                if tag:
                    tag_name = get_localized_field(tag, 'name', user.language)
                    active_filters.append(f"{tag.icon} {tag_name}")
    filter_text = "\n".join(active_filters) if active_filters else i18n.get("none", user.language, default="None")
    text = (
        f"🔍 <b>{i18n.get('routes_found', user.language, default='Routes found')}: {len(routes)}</b>\n\n"
        f"<b>{i18n.get('active_filters', user.language, default='Active filters')}:</b>\n{filter_text}\n\n"
        f"{i18n.get('choose_route', user.language)}"
    )
    await safe_edit_text(
        callback,
        text,
        reply_markup=UserKeyboards.route_list(routes, city_id=city_id, show_filter_button=True, language=user.language)
    )
@router.callback_query(F.data.startswith("filter:reset:"))
async def reset_filters(callback: CallbackQuery, session: AsyncSession, user: User):
    from bot.utils.i18n import i18n
    city_id = int(callback.data.split(":")[2])
    user_id = callback.from_user.id
    if user_id in user_filters:
        user_filters[user_id] = {}
    route_repo = RouteRepository(session)
    routes = await route_repo.get_by_city(city_id)
    await safe_edit_text(
        callback,
        f"🔄 <b>{i18n.get('filters_reset', user.language, default='Filters Reset')}</b>\n\n"
        f"{i18n.get('routes_available', user.language, default='Routes available')}: {len(routes)}\n\n"
        f"{i18n.get('choose_route', user.language)}",
        reply_markup=UserKeyboards.route_list(routes, city_id=city_id, show_filter_button=True, language=user.language)
    )
    await callback.answer(i18n.get("filters_reset", user.language, default="Filters reset"))