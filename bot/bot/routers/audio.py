import logging
from aiogram import Router, F
from aiogram.types import CallbackQuery, FSInputFile
from sqlalchemy.ext.asyncio import AsyncSession
from bot.models.user import User
from bot.models.user_audio_settings import UserAudioSettings
from bot.services.audio_generator import AudioGenerator
from bot.repositories.point import PointRepository
from bot.utils.safe_edit import safe_edit_text
logger = logging.getLogger(__name__)
router = Router()
@router.callback_query(F.data.startswith("audio:play:"))
async def play_audio(callback: CallbackQuery, session: AsyncSession, user: User):
    try:
        point_id = int(callback.data.split(":")[2])
        language = callback.data.split(":")[3] if len(callback.data.split(":")) > 3 else "ru"
        logger.info(f"[AUDIO] Запрос аудио для точки {point_id}, язык: {language}, пользователь: {user.telegram_id}")
        await callback.answer("🎧 Генерация аудио...")
        point_repo = PointRepository(session)
        point = await point_repo.get_with_tasks(point_id)
        if not point:
            logger.warning(f"[AUDIO] Точка {point_id} не найдена")
            await callback.message.answer("Точка не найдена")
            return
        from bot.utils.helpers import get_first_task_text
        first_task_len = len(get_first_task_text(point, language)) if point else 0
        logger.info(f"[AUDIO] Точка найдена: {point.name}, audio_enabled={point.audio_enabled}, audio_text length={len(point.audio_text) if point.audio_text else 0}, task_text length={first_task_len}")
        from sqlalchemy import select
        result = await session.execute(
            select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
        )
        audio_settings = result.scalar_one_or_none()
        voice_id = audio_settings.voice_id if audio_settings and audio_settings.voice_id is not None else 0
        audio_gen = AudioGenerator(session)
        audio_path = await audio_gen.get_audio_for_point(
            point_id,
            language,
            None,
            voice_id
        )
        logger.info(f"[AUDIO] Путь к аудио: {audio_path}")
        if not audio_path:
            logger.warning(f"[AUDIO] Аудио не сгенерировано для точки {point_id}")
            await callback.message.answer("Аудиогид недоступен для этой точки. Убедитесь, что аудиогид включен в настройках точки.")
            return
        import os
        if not os.path.exists(audio_path):
            logger.error(f"[AUDIO] Файл не найден: {audio_path}")
            await callback.message.answer("Ошибка: аудиофайл не найден")
            return
        logger.info(f"[AUDIO] Отправка аудио: {audio_path}")
        await callback.message.answer_audio(
            audio=FSInputFile(audio_path),
            title=f"Аудиогид: {point.name}",
            performer="QuestGuideRF"
        )
        logger.info(f"[AUDIO] Аудио успешно отправлено пользователю {user.telegram_id}")
    except Exception as e:
        logger.error(f"[AUDIO] Ошибка воспроизведения аудио: {e}", exc_info=True)
        try:
            await callback.message.answer(f"Ошибка воспроизведения: {str(e)[:100]}")
        except Exception as send_err:
            logger.warning("[AUDIO] Не удалось отправить сообщение об ошибке пользователю: %s", send_err)
@router.callback_query(F.data.startswith("audio:toggle_autoplay"))
async def toggle_autoplay(callback: CallbackQuery, session: AsyncSession, user: User):
    try:
        from sqlalchemy import select
        result = await session.execute(
            select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
        )
        settings = result.scalars().first()
        if not settings:
            settings = UserAudioSettings(user_id=user.id, auto_play=True)
            session.add(settings)
        else:
            settings.auto_play = not settings.auto_play
        await session.commit()
        from bot.utils.i18n import i18n
        status = i18n.get("audio_autoplay_enabled", user.language) if settings.auto_play else i18n.get("audio_autoplay_disabled", user.language)
        icon = "🔊" if settings.auto_play else "🔇"
        await callback.answer(
            f"{icon} {i18n.get('audio_autoplay', user.language)} {status}",
            show_alert=True
        )
    except Exception as e:
        logger.error(f"Error toggling autoplay: {e}")
        from bot.utils.i18n import i18n
        await callback.answer(i18n.get("error", user.language), show_alert=True)
@router.callback_query(F.data.startswith("audio:change_language:"))
async def change_audio_language(callback: CallbackQuery, session: AsyncSession, user: User):
    try:
        language = callback.data.split(":")[2]
        from sqlalchemy import select
        result = await session.execute(
            select(UserAudioSettings).where(UserAudioSettings.user_id == user.id)
        )
        settings = result.scalars().first()
        if not settings:
            settings = UserAudioSettings(user_id=user.id, language=language)
            session.add(settings)
        else:
            settings.language = language
        await session.commit()
        lang_names = {
            'ru': '🇷🇺 Русский',
            'en': '🇬🇧 English',
            'de': '🇩🇪 Deutsch',
            'fr': '🇫🇷 Français',
            'es': '🇪🇸 Español'
        }
        await callback.answer(
            f"Язык изменен на {lang_names.get(language, language)}",
            show_alert=True
        )
    except Exception as e:
        logger.error(f"Error changing language: {e}")
        await callback.answer("Ошибка", show_alert=True)