import logging
from aiogram import Bot
from aiogram.types import BotCommand, BotCommandScopeChat
logger = logging.getLogger(__name__)
COMMANDS_NORMAL = {
    'ru': [
        BotCommand(command="start", description="Начать / Главное меню"),
        BotCommand(command="web", description="Вход на сайт"),
        BotCommand(command="token", description="Банк грошей"),
        BotCommand(command="promo", description="Ввести промокод"),
        BotCommand(command="top", description="Топ маршрутов"),
        BotCommand(command="review", description="Оставить отзыв"),
        BotCommand(command="commands", description="Все команды (кнопками)"),
        BotCommand(command="partner", description="Партнерка"),
        BotCommand(command="become_creator", description="Стать создателем"),
    ],
    'en': [
        BotCommand(command="start", description="Start / Main menu"),
        BotCommand(command="web", description="Access website"),
        BotCommand(command="token", description="Token bank"),
        BotCommand(command="promo", description="Enter promo code"),
        BotCommand(command="top", description="Top routes"),
        BotCommand(command="review", description="Leave review"),
        BotCommand(command="commands", description="All commands (buttons)"),
        BotCommand(command="partner", description="Referral program"),
        BotCommand(command="become_creator", description="Become creator"),
    ],
}
COMMANDS_QUEST = {
    'ru': [
        BotCommand(command="restart_point", description="🔄 Перезапустить точку"),
        BotCommand(command="cancel_quest", description="❌ Выйти из квеста"),
    ],
    'en': [
        BotCommand(command="restart_point", description="🔄 Restart point"),
        BotCommand(command="cancel_quest", description="❌ Exit quest"),
    ],
}
async def set_user_commands(bot_instance: Bot, chat_id: int, lang: str = 'ru', in_quest: bool = False) -> None:
    if lang not in ('ru', 'en'):
        lang = 'ru'
    try:
        commands = COMMANDS_QUEST[lang] if in_quest else COMMANDS_NORMAL[lang]
        scope = BotCommandScopeChat(chat_id=chat_id)
        await bot_instance.set_my_commands(commands, scope=scope)
    except Exception as e:
        logger.debug(f"Could not set commands for chat {chat_id}: {e}")