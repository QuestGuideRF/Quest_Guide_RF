#!/bin/bash
if pgrep -f "python.*bot.main" > /dev/null 2>&1; then
    echo "ℹ️  Бот уже запущен. Пропускаю запуск."
    exit 0
fi
if ps aux | grep -v grep | grep -q "python.*bot.main"; then
    echo "ℹ️  Бот уже запущен. Пропускаю запуск."
    exit 0
fi
cd ~/www/questguiderf.ru/bot || exit 1
if [ ! -d "venv" ]; then
    echo "ОШИБКА: venv не найден! Создайте виртуальное окружение: python3 -m venv venv"
    exit 1
fi
source venv/bin/activate || exit 1
pip install --upgrade pip --quiet 2>/dev/null || true
if ! python -c "import aiogram" 2>/dev/null; then
    echo "Установка зависимостей..."
    pip install -r requirements.txt || {
        echo "ОШИБКА: Не удалось установить зависимости"
        echo "Попробуйте вручную: cd ~/www/questguiderf.ru/bot && source venv/bin/activate && pip install -r requirements.txt"
        exit 1
    }
fi
cd bot || exit 1
export OPENBLAS_NUM_THREADS=1
export OMP_NUM_THREADS=1
export PYTHONPATH="$(pwd)/..:${PYTHONPATH}"
echo "🚀 Запуск бота..."
python -m bot.main