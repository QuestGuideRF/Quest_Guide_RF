from aiogram import Bot, Dispatcher
from aiogram.fsm.storage.memory import MemoryStorage
from aiogram.client.session.aiohttp import AiohttpSession
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from bot.config import load_config
config = load_config()
_session = AiohttpSession(timeout=1440)
bot = Bot(token=config.bot.token, session=_session)
storage = MemoryStorage()
dp = Dispatcher(storage=storage)
engine = create_async_engine(
    config.db.url,
    echo=False,
    pool_pre_ping=True,
)
SessionLocal = async_sessionmaker(
    engine,
    class_=AsyncSession,
    expire_on_commit=False,
)
async def get_session() -> AsyncSession:
    async with SessionLocal() as session:
        yield session