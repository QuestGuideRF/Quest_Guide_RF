from typing import List
from typing import List
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton, ReplyKeyboardMarkup, KeyboardButton
from aiogram.utils.keyboard import InlineKeyboardBuilder
from bot.models.city import City
from bot.models.route import Route
class UserKeyboards:
    @staticmethod
    def language_selection() -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text="🇷🇺 Русский", callback_data="lang:ru"),
        )
        builder.row(
            InlineKeyboardButton(text="🇬🇧 English", callback_data="lang:en"),
        )
        return builder.as_markup()
    @staticmethod
    def main_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text=i18n.get("select_city", lang), callback_data="select_city"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("menu_bank", lang), callback_data="open_bank"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("my_stats", lang), callback_data="my_stats"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("partner", lang), callback_data="open_partner"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("settings", lang), callback_data="settings"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("about", lang), callback_data="about"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("become_creator", lang), callback_data="become_creator"),
        )
        return builder.as_markup()
    @staticmethod
    def partner_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("partner_levels_info_btn", lang, default="📋 Подробная информация"),
                callback_data="partner:levels_info",
            ),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", lang), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def commands_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text=i18n.get("home", lang), callback_data="back_to_main"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("menu_bank", lang), callback_data="open_bank"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("enter_promo_code", lang), callback_data="promo:menu"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("top_routes", lang), callback_data="show_top"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("leave_review", lang), callback_data="open_review"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("partner", lang), callback_data="open_partner"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("web_login_button", lang), callback_data="open_web"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("become_creator", lang), callback_data="become_creator"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("settings", lang), callback_data="settings"),
        )
        return builder.as_markup()
    @staticmethod
    def promo_menu(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("promo_enter_code_btn", lang, default="✏️ Ввести промокод"),
                callback_data="promo:enter",
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("promo_my_activations_btn", lang, default="📋 Мои активации"),
                callback_data="promo:my_list",
            )
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", lang), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def promo_activated(route_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("start_quest", lang),
                callback_data=f"start_quest:{route_id}",
            )
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("home", lang), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def promo_my_list_back(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("promo_enter_code_btn", lang, default="✏️ Ввести промокод"),
                callback_data="promo:enter",
            )
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", lang), callback_data="promo:menu"),
        )
        return builder.as_markup()
    @staticmethod
    def photo_confirm(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("photo_confirm_send", lang, default="✅ Отправить это фото"),
                callback_data="photo_confirm:yes",
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("photo_confirm_retake", lang, default="📸 Переснять"),
                callback_data="photo_confirm:retake",
            )
        )
        return builder.as_markup()
    @staticmethod
    def settings_menu(lang: str = "ru", show_map: bool = False) -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text=i18n.get("change_language", lang), callback_data="settings:language"),
        )
        show_map_text = i18n.get("show_map_on", lang) if show_map else i18n.get("show_map_off", lang)
        builder.row(
            InlineKeyboardButton(text=show_map_text, callback_data="settings:show_map"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("audio_settings", lang), callback_data="settings:audio"),
        )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", lang), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def city_list(cities: List[City], language: str = 'ru') -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for city in cities:
            city_name = get_localized_field(city, 'name', language)
            builder.row(
                InlineKeyboardButton(
                    text=city_name,
                    callback_data=f"city:{city.id}",
                )
            )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", language), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def route_list(routes: List[Route], city_id: int = None, show_filter_button: bool = False, language: str = 'ru') -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n, get_localized_field
        builder = InlineKeyboardBuilder()
        for route in routes:
            icon = "🚶" if route.route_type.value == "walking" else "🚴"
            route_name = get_localized_field(route, 'name', language)
            text = f"{icon} {route_name} - {route.price} грошей"
            builder.row(
                InlineKeyboardButton(
                    text=text,
                    callback_data=f"route:{route.id}",
                )
            )
        if show_filter_button and city_id:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("filters", language, default="🔍 Filters"),
                    callback_data=f"filter:menu:{city_id}"
                )
            )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", language), callback_data="select_city"),
        )
        return builder.as_markup()
    @staticmethod
    def route_detail(route_id: int, has_paid: bool = False, language: str = 'ru', show_promo: bool = False) -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        if has_paid:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("start_quest", language),
                    callback_data=f"start_quest:{route_id}",
                )
            )
        else:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("pay_with_tokens", language),
                    callback_data=f"pay:{route_id}",
                )
            )
            if show_promo:
                builder.row(
                    InlineKeyboardButton(
                        text=i18n.get("enter_promo_code", language, default="🎫 Ввести промокод"),
                        callback_data=f"promo_code:{route_id}",
                    )
                )
        builder.row(
            InlineKeyboardButton(text=i18n.get("back", language), callback_data="back_to_routes"),
        )
        return builder.as_markup()
    @staticmethod
    def quest_menu(route_id: int, language: str = 'ru', is_paused: bool = False) -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        if is_paused:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("resume_quest", language, default="▶️ Продолжить квест"),
                    callback_data=f"resume_quest:{route_id}",
                )
            )
        else:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("pause_quest", language, default="⏸️ Пауза"),
                    callback_data=f"pause_quest:{route_id}",
                )
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel_quest", language),
                callback_data=f"cancel_quest:{route_id}",
            )
        )
        return builder.as_markup()
    @staticmethod
    def paused_quest_menu(route_id: int, language: str = 'ru') -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("resume_quest", language, default="▶️ Продолжить квест"),
                callback_data=f"resume_quest:{route_id}",
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("cancel_quest", language, default="❌ Отменить квест"),
                callback_data=f"cancel_quest:{route_id}",
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back_to_main", language, default="🏠 Главное меню"),
                callback_data="back_to_main",
            )
        )
        return builder.as_markup()
    @staticmethod
    def quest_completed(progress_id: int = None, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        if progress_id:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("take_quiz", lang, default="📝 Пройти квиз"),
                    callback_data=f"quiz:start:{progress_id}"
                ),
            )
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("leave_review", lang, default="⭐ Оставить отзыв"),
                    callback_data=f"review:select:{progress_id}"
                ),
            )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back_to_main", lang, default="🏠 Главное меню"),
                callback_data="back_to_main"
            ),
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("select_another_route", lang, default="🔄 Выбрать другой маршрут"),
                callback_data="select_city"
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_first(progress_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("continue_btn", lang, default="Продолжить"),
                callback_data=f"quiz:start:{progress_id}",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_continue_survey(progress_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("continue_btn", lang, default="Продолжить"),
                callback_data=f"survey:start:{progress_id}",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_review_prompt(progress_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("leave_review", lang, default="⭐ Оставить отзыв"),
                callback_data=f"review:select:{progress_id}",
            ),
            InlineKeyboardButton(
                text=i18n.get("skip", lang, default="⏭ Пропустить"),
                callback_data=f"post_quest:skip_to_quiz:{progress_id}",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_quiz_prompt(progress_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("take_quiz", lang, default="📝 Пройти квиз"),
                callback_data=f"quiz:start:{progress_id}",
            ),
            InlineKeyboardButton(
                text=i18n.get("skip", lang, default="⏭ Пропустить"),
                callback_data=f"post_quest:final:{progress_id}",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_final(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("view_certificate", lang, default="📜 Посмотреть сертификат"),
                callback_data="from_review:web",
            ),
            InlineKeyboardButton(
                text=i18n.get("back_to_main", lang, default="🏠 Главное меню"),
                callback_data="back_to_main",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def post_quest_after_survey(progress_id: int, lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("leave_review", lang, default="⭐ Оставить отзыв"),
                callback_data=f"review:select:{progress_id}",
            ),
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back_to_main", lang, default="🏠 Главное меню"),
                callback_data="back_to_main",
            ),
            InlineKeyboardButton(
                text=i18n.get("view_certificate", lang, default="📜 Посмотреть сертификат"),
                callback_data="from_review:web",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def review_done_only_main_and_certificate(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back_to_main", lang, default="🏠 Главное меню"),
                callback_data="from_review:main_menu",
            ),
            InlineKeyboardButton(
                text=i18n.get("view_certificate", lang, default="📜 Посмотреть сертификат"),
                callback_data="from_review:web",
            ),
        )
        return builder.as_markup()
    @staticmethod
    def cancel_keyboard(lang: str = "ru") -> ReplyKeyboardMarkup:
        from bot.utils.i18n import i18n
        return ReplyKeyboardMarkup(
            keyboard=[
                [KeyboardButton(text=i18n.get("cancel", lang, default="❌ Отмена"))],
            ],
            resize_keyboard=True,
        )
    @staticmethod
    def cancel_inline(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text=i18n.get("cancel", lang, default="❌ Отмена"), callback_data="back_to_main"),
        )
        return builder.as_markup()
    @staticmethod
    def no_active_quest_keyboard(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(text=i18n.get("back_to_main", lang, default="🏠 Главное меню"), callback_data="back_to_main"),
            InlineKeyboardButton(text=i18n.get("route_list", lang), callback_data="select_city"),
        )
        return builder.as_markup()
    @staticmethod
    def point_hint_keyboard(point_id: int, hints_used: int, max_hints: int, can_use_hint: bool = True, language: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        if can_use_hint and hints_used < max_hints:
            remaining = max_hints - hints_used
            builder.row(
                InlineKeyboardButton(
                    text=f"💡 {i18n.get('hint', language)} ({remaining} {i18n.get('hints_left', language)})",
                    callback_data=f"hint:request:{point_id}",
                )
            )
        elif hints_used >= max_hints:
            builder.row(
                InlineKeyboardButton(
                    text=i18n.get("hints_used_up", language),
                    callback_data="hint:none",
                )
            )
        return builder.as_markup()
    @staticmethod
    def hint_level_selection(point_id: int, available_levels: List[int], used_levels: List[int], language: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        level_names = {
            1: i18n.get("hint_level_easy_btn", language),
            2: i18n.get("hint_level_medium_btn", language),
            3: i18n.get("hint_level_detailed_btn", language),
        }
        for level in sorted(available_levels):
            if level not in used_levels:
                builder.row(
                    InlineKeyboardButton(
                        text=level_names.get(level, f"Уровень {level}"),
                        callback_data=f"hint:show:{point_id}:{level}",
                    )
                )
        builder.row(
            InlineKeyboardButton(text=i18n.get("cancel", language), callback_data="hint:cancel"),
        )
        return builder.as_markup()
    @staticmethod
    def get_audio_keyboard(point_id: int, audio_enabled: bool = True, language: str = "ru") -> InlineKeyboardMarkup:
        builder = InlineKeyboardBuilder()
        if audio_enabled:
            builder.row(
                InlineKeyboardButton(
                    text="🎧 Аудиогид",
                    callback_data=f"audio:play:{point_id}:{language}",
                )
            )
        return builder.as_markup()
    @staticmethod
    def get_audio_settings_keyboard(lang: str = "ru") -> InlineKeyboardMarkup:
        from bot.utils.i18n import i18n
        builder = InlineKeyboardBuilder()
        builder.row(
            InlineKeyboardButton(
                text=f"▶️ {i18n.get('audio_autoplay', lang)}",
                callback_data="audio_settings:toggle_autoplay"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=f"👤 {i18n.get('audio_voice', lang)}",
                callback_data="audio_settings:voice"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=f"⚡ {i18n.get('audio_rate', lang)}",
                callback_data="audio_settings:rate"
            )
        )
        builder.row(
            InlineKeyboardButton(
                text=i18n.get("back", lang),
                callback_data="settings"
            )
        )
        return builder.as_markup()