import asyncio
import sys
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker
from bot.config import load_config
from bot.models.user import User, UserRole
from bot.repositories.user import UserRepository
async def add_admin(telegram_id: int):
    config = load_config()
    engine = create_async_engine(config.db.url, echo=False)
    SessionLocal = async_sessionmaker(engine, expire_on_commit=False)
    async with SessionLocal() as session:
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(telegram_id)
        if user:
            user = await user_repo.set_role(telegram_id, UserRole.ADMIN)
            print(f"✅ Пользователь {telegram_id} теперь администратор!")
        else:
            print(f"⚠️  Пользователь {telegram_id} не найден в базе.")
            print(f"Пользователь будет добавлен как администратор при первом запуске бота.")
            user = await user_repo.create(
                telegram_id=telegram_id,
                role=UserRole.ADMIN,
            )
            print(f"✅ Создан администратор {telegram_id}")
    await engine.dispose()
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Использование: python scripts/add_admin.py <telegram_id>")
        print("Пример: python scripts/add_admin.py 123456789")
        sys.exit(1)
    try:
        telegram_id = int(sys.argv[1])
        asyncio.run(add_admin(telegram_id))
    except ValueError:
        print("❌ Telegram ID должен быть числом!")
        sys.exit(1)