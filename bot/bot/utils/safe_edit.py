import logging
from typing import Optional, Union
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup
logger = logging.getLogger(__name__)
async def safe_edit_text(
    message_or_callback: Union[Message, CallbackQuery],
    text: str,
    reply_markup: Optional[InlineKeyboardMarkup] = None,
    parse_mode: Optional[str] = "HTML"
) -> bool:
    try:
        if isinstance(message_or_callback, CallbackQuery):
            await message_or_callback.message.edit_text(
                text,
                reply_markup=reply_markup,
                parse_mode=parse_mode
            )
        else:
            await message_or_callback.edit_text(
                text,
                reply_markup=reply_markup,
                parse_mode=parse_mode
            )
        return True
    except Exception as e:
        if "not modified" not in str(e).lower():
            logger.error(f"Ошибка редактирования сообщения: {e}")
        return False