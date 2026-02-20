import os
os.environ.setdefault("HF_HUB_DISABLE_XET", "1")
import asyncio
import logging
import traceback
from pathlib import Path
from aiogram import Dispatcher
from sqlalchemy import text
from bot.loader import bot, dp, engine
from bot.models import Base
from bot.middlewares import DbSessionMiddleware, UserMiddleware, DebounceCallbackMiddleware
from bot.middlewares.ban_check import BanCheckMiddleware
from bot.routers import user_router, admin_router, payment_router, web_auth_router, hints_router, admin_hints_router, audio_router, filters_router, bank_router, moderator_router
from bot.routers.admin_bans import router as admin_bans_router
from bot.routers.reviews import router as reviews_router
from bot.routers.quiz import router as quiz_router
from bot.routers.survey import router as survey_router
from bot.loader import config
from bot.services.admin_notifier import AdminNotifier
from aiogram import Router
from aiogram.types import ErrorEvent
from aiogram.exceptions import TelegramNetworkError
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger(__name__)
async def set_bot_commands():
    from aiogram.types import BotCommandScopeDefault
    from bot.utils.commands import COMMANDS_NORMAL
    try:
        await bot.set_my_commands(COMMANDS_NORMAL['ru'], scope=BotCommandScopeDefault(), language_code="ru")
        await bot.set_my_commands(COMMANDS_NORMAL['en'], scope=BotCommandScopeDefault(), language_code="en")
        logger.info("✅ Меню команд установлено")
    except Exception as e:
        logger.warning(f"Не удалось установить команды: {e}")
async def create_tables():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
    logger.info("✅ Таблицы БД созданы")
async def check_db_connection():
    try:
        async with engine.connect() as conn:
            await conn.execute(text("SELECT 1"))
        logger.info("✅ Подключение к БД успешно")
        return True
    except Exception as e:
        logger.error(f"❌ Ошибка подключения к БД: {e}")
        return False
def setup_routers(dp: Dispatcher):
    dp.include_router(user_router)
    dp.include_router(admin_router)
    dp.include_router(payment_router)
    dp.include_router(web_auth_router)
    dp.include_router(hints_router)
    dp.include_router(admin_hints_router)
    dp.include_router(admin_bans_router)
    dp.include_router(audio_router)
    dp.include_router(reviews_router)
    dp.include_router(quiz_router)
    dp.include_router(survey_router)
    dp.include_router(filters_router)
    dp.include_router(bank_router)
    dp.include_router(moderator_router)
    logger.info("✅ Роутеры подключены")
def _setup_error_handlers(dp: Dispatcher):
    errors_router = Router()
    @errors_router.error()
    async def _telegram_network_error_handler(event: ErrorEvent):
        if isinstance(event.exception, TelegramNetworkError):
            err_str = str(event.exception).lower()
            if "timeout" in err_str or "network" in err_str:
                logger.info(
                    "Telegram API timeout/network (update_id=%s), часто временно: %s",
                    getattr(event.update, "update_id", None),
                    event.exception,
                )
            else:
                logger.warning(
                    "Telegram API error (update_id=%s): %s",
                    getattr(event.update, "update_id", None),
                    event.exception,
                )
            return
        raise event.exception
    dp.include_router(errors_router)
def setup_middlewares(dp: Dispatcher):
    dp.message.middleware(DbSessionMiddleware())
    dp.callback_query.middleware(DebounceCallbackMiddleware())
    dp.callback_query.middleware(DbSessionMiddleware())
    dp.message.middleware(UserMiddleware())
    dp.callback_query.middleware(UserMiddleware())
    dp.message.middleware(BanCheckMiddleware())
    dp.callback_query.middleware(BanCheckMiddleware())
    from bot.middlewares.channel_check import ChannelCheckMiddleware
    dp.message.middleware(ChannelCheckMiddleware())
    dp.callback_query.middleware(ChannelCheckMiddleware())
    logger.info("✅ Middleware подключены")
async def on_startup():
    logger.info("🚀 Запуск бота...")
    error_log_path = Path("bot_errors.log")
    error_log = None
    if error_log_path.exists():
        try:
            with open(error_log_path, 'r', encoding='utf-8') as f:
                error_log = f.read()
            error_log_path.unlink()
        except Exception as e:
            logger.error(f"Не удалось прочитать лог ошибок: {e}")
    if not await check_db_connection():
        logger.error("❌ Не удалось подключиться к БД. Остановка.")
        return False
    await create_tables()
    setup_routers(dp)
    setup_middlewares(dp)
    _setup_error_handlers(dp)
    await set_bot_commands()
    logger.info("✅ Бот успешно запущен!")
    try:
        admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
        from bot.loader import SessionLocal
        async with SessionLocal() as session:
            await admin_notifier.notify_bot_restart(error_log, session=session)
    except Exception as e:
        logger.error(f"Не удалось отправить уведомление о запуске: {e}")
    if getattr(config, 'channel', None) and (config.channel.channel_id or config.channel.channel_username):
        try:
            from bot.services.channel_stats import run_daily_channel_stats
            asyncio.create_task(run_daily_channel_stats(
                bot,
                getattr(config.channel, 'channel_id', None),
                getattr(config.channel, 'channel_username', None),
                list(config.bot.admin_ids or []),
                engine,
            ))
        except Exception as e:
            logger.warning("Не удалось запустить задачу статистики канала: %s", e)
    return True
async def on_shutdown():
    logger.info("🛑 Остановка бота...")
    await bot.session.close()
    await engine.dispose()
    logger.info("✅ Бот остановлен")
async def main():
    try:
        if not await on_startup():
            return
        await dp.start_polling(bot, allowed_updates=dp.resolve_used_update_types())
    except (KeyboardInterrupt, SystemExit):
        logger.info("Получен сигнал остановки")
    except Exception as e:
        error_msg = f"Критическая ошибка: {str(e)}"
        error_details = traceback.format_exc()
        logger.critical(error_msg, exc_info=True)
        try:
            with open("bot_errors.log", "w", encoding="utf-8") as f:
                f.write(f"{error_msg}\n\n{error_details}")
        except Exception:
            pass
        try:
            admin_notifier = AdminNotifier(bot, config.bot.admin_ids)
            await admin_notifier.notify_critical_error(error_msg, error_details)
        except Exception as notify_error:
            logger.error(f"Не удалось отправить уведомление об ошибке: {notify_error}")
        raise
    finally:
        await on_shutdown()
if __name__ == "__main__":
    asyncio.run(main())