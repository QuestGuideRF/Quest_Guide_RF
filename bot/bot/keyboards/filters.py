from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton
from typing import List, Dict, Set
class FilterKeyboards:
    @staticmethod
    def get_filter_menu(city_id: int) -> InlineKeyboardMarkup:
        keyboard = [
            [InlineKeyboardButton(text="🎨 По теме", callback_data=f"filter:topics:{city_id}")],
            [InlineKeyboardButton(text="👨‍👩‍👧 По возрасту", callback_data=f"filter:age:{city_id}")],
            [InlineKeyboardButton(text="⭐ По сложности", callback_data=f"filter:difficulty:{city_id}")],
            [InlineKeyboardButton(text="⏱️ По длительности", callback_data=f"filter:duration:{city_id}")],
            [InlineKeyboardButton(text="🌦️ По сезону", callback_data=f"filter:season:{city_id}")],
            [InlineKeyboardButton(text="🔄 Сбросить фильтры", callback_data=f"filter:reset:{city_id}")],
            [InlineKeyboardButton(text="« Назад", callback_data=f"city:{city_id}")]
        ]
        return InlineKeyboardMarkup(inline_keyboard=keyboard)
    @staticmethod
    def get_topic_filters(city_id: int, tags: List[Dict], selected: Set[int]) -> InlineKeyboardMarkup:
        keyboard = []
        for tag in tags:
            checkbox = "✅" if tag['id'] in selected else "☐"
            keyboard.append([
                InlineKeyboardButton(
                    text=f"{checkbox} {tag['icon']} {tag['name']}",
                    callback_data=f"filter:toggle:topic:{tag['id']}:{city_id}"
                )
            ])
        keyboard.append([InlineKeyboardButton(text="✔️ Применить", callback_data=f"filter:apply:{city_id}")])
        keyboard.append([InlineKeyboardButton(text="« Назад", callback_data=f"filter:menu:{city_id}")])
        return InlineKeyboardMarkup(inline_keyboard=keyboard)
    @staticmethod
    def get_age_filters(city_id: int, tags: List[Dict], selected: Set[int]) -> InlineKeyboardMarkup:
        keyboard = []
        for tag in tags:
            radio = "🔘" if tag['id'] in selected else "○"
            keyboard.append([
                InlineKeyboardButton(
                    text=f"{radio} {tag['icon']} {tag['name']}",
                    callback_data=f"filter:select:age:{tag['id']}:{city_id}"
                )
            ])
        keyboard.append([InlineKeyboardButton(text="✔️ Применить", callback_data=f"filter:apply:{city_id}")])
        keyboard.append([InlineKeyboardButton(text="« Назад", callback_data=f"filter:menu:{city_id}")])
        return InlineKeyboardMarkup(inline_keyboard=keyboard)
    @staticmethod
    def get_difficulty_filters(city_id: int, tags: List[Dict], selected: Set[int]) -> InlineKeyboardMarkup:
        keyboard = []
        for tag in tags:
            checkbox = "✅" if tag['id'] in selected else "☐"
            keyboard.append([
                InlineKeyboardButton(
                    text=f"{checkbox} {tag['icon']} {tag['name']}",
                    callback_data=f"filter:toggle:difficulty:{tag['id']}:{city_id}"
                )
            ])
        keyboard.append([InlineKeyboardButton(text="✔️ Применить", callback_data=f"filter:apply:{city_id}")])
        keyboard.append([InlineKeyboardButton(text="« Назад", callback_data=f"filter:menu:{city_id}")])
        return InlineKeyboardMarkup(inline_keyboard=keyboard)
    @staticmethod
    def get_duration_filters(city_id: int, tags: List[Dict], selected: Set[int]) -> InlineKeyboardMarkup:
        keyboard = []
        for tag in tags:
            checkbox = "✅" if tag['id'] in selected else "☐"
            keyboard.append([
                InlineKeyboardButton(
                    text=f"{checkbox} {tag['icon']} {tag['name']}",
                    callback_data=f"filter:toggle:duration:{tag['id']}:{city_id}"
                )
            ])
        keyboard.append([InlineKeyboardButton(text="✔️ Применить", callback_data=f"filter:apply:{city_id}")])
        keyboard.append([InlineKeyboardButton(text="« Назад", callback_data=f"filter:menu:{city_id}")])
        return InlineKeyboardMarkup(inline_keyboard=keyboard)
    @staticmethod
    def get_season_filters(city_id: int, tags: List[Dict], selected: Set[int]) -> InlineKeyboardMarkup:
        keyboard = []
        for tag in tags:
            checkbox = "✅" if tag['id'] in selected else "☐"
            keyboard.append([
                InlineKeyboardButton(
                    text=f"{checkbox} {tag['icon']} {tag['name']}",
                    callback_data=f"filter:toggle:season:{tag['id']}:{city_id}"
                )
            ])
        keyboard.append([InlineKeyboardButton(text="✔️ Применить", callback_data=f"filter:apply:{city_id}")])
        keyboard.append([InlineKeyboardButton(text="« Назад", callback_data=f"filter:menu:{city_id}")])
        return InlineKeyboardMarkup(inline_keyboard=keyboard)