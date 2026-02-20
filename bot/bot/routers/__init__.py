from bot.routers.user import router as user_router
from bot.routers.admin import router as admin_router
from bot.routers.payments import router as payment_router
from bot.routers.web_auth import router as web_auth_router
from bot.routers.hints import router as hints_router
from bot.routers.admin_hints import router as admin_hints_router
from bot.routers.admin_bans import router as admin_bans_router
from bot.routers.audio import router as audio_router
from bot.routers.filters import router as filters_router
from bot.routers.bank import router as bank_router
from bot.routers.moderator import router as moderator_router
__all__ = ["admin_router", "user_router", "payment_router", "web_auth_router", "hints_router", "admin_hints_router", "admin_bans_router", "audio_router", "filters_router", "bank_router", "moderator_router"]