import os
from pathlib import Path
from typing import List, Dict, Optional
from aiogram import Bot
from aiogram.types import PhotoSize
from bot.models.point import Point
from bot.models.task import Task
async def download_photo(bot: Bot, photo: PhotoSize, download_dir: str = "photos") -> str:
    return await download_photo_by_file_id(bot, photo.file_id, download_dir)
async def download_photo_by_file_id(bot: Bot, file_id: str, download_dir: str = "photos") -> str:
    project_root = Path(__file__).parent.parent.parent.parent
    photos_path = project_root / download_dir
    photos_path.mkdir(parents=True, exist_ok=True)
    file = await bot.get_file(file_id)
    file_path = photos_path / f"{file_id}.jpg"
    await bot.download_file(file.file_path, str(file_path))
    return str(file_path)
def format_duration(minutes: int) -> str:
    if minutes < 60:
        return f"{minutes} мин"
    hours = minutes // 60
    mins = minutes % 60
    if mins == 0:
        return f"{hours} ч"
    return f"{hours} ч {mins} мин"
def format_distance(km: float) -> str:
    if km < 1:
        return f"{int(km * 1000)} м"
    return f"{km:.1f} км"
def yandex_maps_url(latitude: float, longitude: float, zoom: int = 17) -> str:
    return f"https://yandex.ru/maps/?pt={longitude},{latitude}&z={zoom}"
def _task_to_dict(task) -> Dict:
    return {
        'id': task.id,
        'task_text': task.task_text,
        'task_text_en': getattr(task, 'task_text_en', None),
        'task_type': task.task_type,
        'text_answer': getattr(task, 'text_answer', None),
        'text_answer_hint': getattr(task, 'text_answer_hint', None),
        'accept_partial_match': getattr(task, 'accept_partial_match', False),
        'max_attempts': getattr(task, 'max_attempts', 3),
        'order': getattr(task, 'order', 0),
    }
def tasks_from_models(task_list: List) -> List[Dict]:
    return [_task_to_dict(t) for t in sorted(task_list, key=lambda x: getattr(x, 'order', 0))]
def get_point_tasks(point: Point) -> List[Dict]:
    tasks = []
    if hasattr(point, 'tasks') and point.tasks:
        for task in point.tasks:
            tasks.append(_task_to_dict(task))
    return sorted(tasks, key=lambda t: t.get('order', 0))
def get_first_task_text(point, language: str = 'ru') -> str:
    tasks = get_point_tasks(point)
    if not tasks:
        return ''
    t = tasks[0]
    if language == 'en' and t.get('task_text_en'):
        return t['task_text_en'] or ''
    return t.get('task_text') or ''
def parse_task_text(text: str) -> Dict[str, str]:
    result = {
        'directions': '',
        'task': '',
        'hint': ''
    }
    if not text:
        return result
    directions_keywords = ['🚇', 'Как добраться', 'как добраться', 'Как доехать', 'как доехать', 'Метро', 'метро', 'Станция', 'станция', '👣', 'Куда идти', 'How to get there', 'how to get there', 'Subway', 'subway', 'Station', 'station', 'Where to go']
    hint_keywords = ['💡', 'Подсказка', 'подсказка', 'Подсказки', 'подсказки', 'Hint', 'hint', 'Hints', 'hints']
    lines = text.split('\n')
    current_section = 'task'
    directions_lines = []
    task_lines = []
    hint_lines = []
    for line in lines:
        line_stripped = line.strip()
        if not line_stripped:
            if current_section == 'directions':
                directions_lines.append('')
            elif current_section == 'task':
                task_lines.append('')
            elif current_section == 'hint':
                hint_lines.append('')
            continue
        is_directions = any(keyword in line_stripped for keyword in directions_keywords)
        is_hint = any(keyword in line_stripped for keyword in hint_keywords)
        if is_directions:
            current_section = 'directions'
            line_clean = line_stripped
            for kw in sorted(directions_keywords, key=len, reverse=True):
                if kw in line_clean:
                    line_clean = line_clean.replace(kw, '', 1).strip()
                    break
            if line_clean:
                directions_lines.append(line_clean)
        elif is_hint:
            current_section = 'hint'
            line_clean = line_stripped
            for kw in sorted(hint_keywords, key=len, reverse=True):
                if kw in line_clean:
                    line_clean = line_clean.replace(kw, '', 1).strip()
                    break
            if line_clean:
                hint_lines.append(line_clean)
        else:
            if current_section == 'directions':
                directions_lines.append(line_stripped)
            elif current_section == 'hint':
                hint_lines.append(line_stripped)
            else:
                task_lines.append(line_stripped)
    result['directions'] = '\n'.join(directions_lines).strip()
    result['task'] = '\n'.join(task_lines).strip()
    result['hint'] = '\n'.join(hint_lines).strip()
    if not result['task'] and not result['directions'] and not result['hint']:
        result['task'] = text
    return result
def split_long_message(text: str, max_length: int = 4000) -> List[str]:
    if len(text) <= max_length:
        return [text]
    parts = []
    current_part = ""
    for line in text.split('\n'):
        if len(current_part) + len(line) + 1 <= max_length:
            if current_part:
                current_part += '\n' + line
            else:
                current_part = line
        else:
            if current_part:
                parts.append(current_part)
            if len(line) > max_length:
                words = line.split(' ')
                temp = ""
                for word in words:
                    if len(temp) + len(word) + 1 <= max_length:
                        if temp:
                            temp += ' ' + word
                        else:
                            temp = word
                    else:
                        if temp:
                            parts.append(temp)
                        temp = word
                current_part = temp
            else:
                current_part = line
    if current_part:
        parts.append(current_part)
    return parts