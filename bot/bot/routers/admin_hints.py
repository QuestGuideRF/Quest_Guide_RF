import logging
import os
import shutil
from pathlib import Path
from datetime import datetime
from aiogram import Router, F
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.fsm.context import FSMContext
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
from bot.loader import bot
from bot.repositories.hint import HintRepository
from bot.repositories.point import PointRepository
from bot.keyboards.admin import get_hints_menu
from bot.fsm.admin_states import AdminHintStates
from bot.loader import config
logger = logging.getLogger(__name__)
router = Router()
def is_admin(user_id: int) -> bool:
    return user_id in config.bot.admin_ids
@router.callback_query(F.data.startswith("admin:point:hints:"))
async def admin_point_hints(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    point_repo = PointRepository(session)
    point = await point_repo.get(point_id)
    if not point:
        await callback.answer("❌ Точка не найдена")
        return
    hint_repo = HintRepository(session)
    hints = await hint_repo.get_by_point(point_id)
    hints_data = [{
        'id': h.id,
        'level': h.level,
        'has_map': h.has_map
    } for h in hints]
    msg_text = (
        f"💡 <b>Подсказки для точки</b>\n"
        f"📍 {point.name}\n\n"
        f"Всего подсказок: {len(hints)}\n\n"
        f"💡 Легкая - общее направление\n"
        f"🔦 Средняя - более конкретное указание\n"
        f"🎯 Детальная - почти точное местоположение"
    )
    await callback.message.edit_text(
        msg_text,
        reply_markup=get_hints_menu(hints_data, point_id),
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:hint:add:"))
async def admin_hint_add_start(callback: CallbackQuery, state: FSMContext, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    point_id = int(callback.data.split(":")[-1])
    point_repo = PointRepository(session)
    point = await point_repo.get(point_id)
    if not point:
        await callback.answer("❌ Точка не найдена")
        return
    await state.update_data(point_id=point_id, route_id=point.route_id)
    await callback.message.answer(
        f"💡 <b>Добавление подсказки</b>\n"
        f"📍 Точка: {point.name}\n\n"
        f"Выберите уровень подсказки:\n\n"
        f"1 - 💡 Легкая (общее направление)\n"
        f"2 - 🔦 Средняя (более конкретное)\n"
        f"3 - 🎯 Детальная (почти точное место)\n\n"
        f"Введите номер (1, 2 или 3):",
        parse_mode="HTML"
    )
    await state.set_state(AdminHintStates.level)
    await callback.answer()
@router.message(AdminHintStates.level)
async def admin_hint_add_level(message: Message, state: FSMContext):
    if message.text not in ["1", "2", "3"]:
        await message.answer("❌ Введите 1, 2 или 3")
        return
    level = int(message.text)
    level_names = {1: "Легкая", 2: "Средняя", 3: "Детальная"}
    await state.update_data(level=level)
    await message.answer(
        f"Уровень: <b>{level_names[level]}</b>\n\n"
        f"Теперь введите текст подсказки:",
        parse_mode="HTML"
    )
    await state.set_state(AdminHintStates.text)
@router.message(AdminHintStates.text)
async def admin_hint_add_text(message: Message, state: FSMContext):
    await state.update_data(text=message.text)
    await message.answer(
        "Хотите добавить мини-карту к подсказке?\n\n"
        "1 - Да, добавить карту\n"
        "2 - Нет, без карты\n\n"
        "Введите номер:"
    )
    await state.set_state(AdminHintStates.has_map)
@router.message(AdminHintStates.has_map)
async def admin_hint_add_has_map(message: Message, state: FSMContext, session: AsyncSession):
    if message.text not in ["1", "2"]:
        await message.answer("❌ Введите 1 или 2")
        return
    has_map = message.text == "1"
    if has_map:
        await state.update_data(has_map=True)
        await message.answer(
            "Загрузите изображение мини-карты с отметкой локации:"
        )
        await state.set_state(AdminHintStates.map_photo)
    else:
        await state.update_data(has_map=False)
        data = await state.get_data()
        await save_hint(session, data, message)
        await state.clear()
@router.message(AdminHintStates.map_photo, F.photo)
async def admin_hint_add_map_photo(message: Message, state: FSMContext, session: AsyncSession):
    try:
        photo = message.photo[-1]
        file = await bot.get_file(photo.file_id)
        data = await state.get_data()
        point_id = data['point_id']
        level = data['level']
        hints_dir = Path("../../uploads/hints")
        hints_dir.mkdir(parents=True, exist_ok=True)
        timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
        filename = f"point_{point_id}_level_{level}_{timestamp}.jpg"
        file_path = hints_dir / filename
        await bot.download_file(file.file_path, str(file_path))
        relative_path = f"/uploads/hints/{filename}"
        await state.update_data(map_image_path=relative_path)
        await save_hint(session, await state.get_data(), message)
        await state.clear()
    except Exception as e:
        logger.error(f"Ошибка при сохранении карты: {e}", exc_info=True)
        await message.answer(f"❌ Ошибка при сохранении карты: {e}")
async def save_hint(session: AsyncSession, data: dict, message: Message):
    try:
        result = await session.execute(
            text(),
            {"point_id": data['point_id']}
        )
        max_order = result.scalar() or 0
        await session.execute(
            text(),
            {
                "point_id": data['point_id'],
                "level": data['level'],
                "text": data['text'],
                "has_map": data.get('has_map', False),
                "map_image_path": data.get('map_image_path'),
                "order": max_order + 1
            }
        )
        await session.commit()
        level_names = {1: "Легкая", 2: "Средняя", 3: "Детальная"}
        await message.answer(
            f"✅ Подсказка добавлена!\n\n"
            f"Уровень: <b>{level_names[data['level']]}</b>\n"
            f"Карта: {'✅ Да' if data.get('has_map') else '❌ Нет'}\n\n"
            f"Используйте /admin для возврата в меню.",
            parse_mode="HTML"
        )
    except Exception as e:
        logger.error(f"Ошибка при сохранении подсказки: {e}", exc_info=True)
        await message.answer(f"❌ Ошибка при сохранении: {e}")
@router.callback_query(F.data.startswith("admin:hint:") & ~F.data.contains("add") & ~F.data.contains("edit") & ~F.data.contains("delete"))
async def admin_hint_view(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    hint_id = int(callback.data.split(":")[-1])
    hint_repo = HintRepository(session)
    hint = await hint_repo.get(hint_id)
    if not hint:
        await callback.answer("❌ Подсказка не найдена")
        return
    level_names = {1: "Легкая", 2: "Средняя", 3: "Детальная"}
    msg_text = (
        f"💡 <b>Подсказка</b>\n\n"
        f"Уровень: <b>{level_names[hint.level]}</b>\n"
        f"Текст: {hint.text}\n"
        f"Карта: {'✅ Есть' if hint.has_map else '❌ Нет'}\n"
    )
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="✏️ Редактировать", callback_data=f"admin:hint:edit:{hint_id}")],
        [InlineKeyboardButton(text="🗑 Удалить", callback_data=f"admin:hint:delete:{hint_id}")],
        [InlineKeyboardButton(text="« Назад", callback_data=f"admin:point:hints:{hint.point_id}")],
    ])
    await callback.message.edit_text(
        msg_text,
        reply_markup=keyboard,
        parse_mode="HTML"
    )
    await callback.answer()
@router.callback_query(F.data.startswith("admin:hint:delete:"))
async def admin_hint_delete(callback: CallbackQuery, session: AsyncSession):
    if not is_admin(callback.from_user.id):
        await callback.answer("❌ Нет прав")
        return
    hint_id = int(callback.data.split(":")[-1])
    hint_repo = HintRepository(session)
    hint = await hint_repo.get(hint_id)
    if not hint:
        await callback.answer("❌ Подсказка не найдена")
        return
    point_id = hint.point_id
    if hint.has_map and hint.map_image_path:
        try:
            map_path = Path("../..") / hint.map_image_path.lstrip("/")
            if map_path.exists():
                map_path.unlink()
        except Exception as e:
            logger.error(f"Ошибка при удалении файла карты: {e}")
    await hint_repo.delete(hint_id)
    await callback.answer("✅ Подсказка удалена")
    callback.data = f"admin:point:hints:{point_id}"
    await admin_point_hints(callback, session)