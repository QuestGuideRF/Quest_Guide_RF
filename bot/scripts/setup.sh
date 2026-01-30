#!/bin/bash
echo "🚀 Настройка Telegram Quest Bot..."
if [ ! -f .env ]; then
    echo "📝 Создание .env файла..."
    cp env.example .env
    echo "⚠️  ВАЖНО: Заполните .env файл своими данными!"
else
    echo "✅ .env файл уже существует"
fi
echo "📁 Создание необходимых директорий..."
mkdir -p photos
mkdir -p alembic/versions
echo "🐳 Запуск Docker контейнеров..."
docker-compose up -d postgres redis
echo "⏳ Ожидание запуска PostgreSQL..."
sleep 5
echo "📊 Инициализация тестовых данных..."
python scripts/init_data.py
echo "✅ Настройка завершена!"
echo ""
echo "Следующие шаги:"
echo "1. Отредактируйте .env файл (добавьте BOT_TOKEN и ADMIN_IDS)"
echo "2. Запустите бота: docker-compose up -d bot"
echo "   или локально: python -m bot.main"
echo ""
echo "Для просмотра логов: docker-compose logs -f bot"