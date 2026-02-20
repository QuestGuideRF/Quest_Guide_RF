#!/bin/bash
BOT_DIR="$HOME/www/questguiderf.ru/bot"
VENV_DIR="$BOT_DIR/venv"
echo "🚀 Установка зависимостей для QuestGuideRF Bot..."
echo ""
cd "$BOT_DIR" || {
    echo "❌ ОШИБКА: Не удалось перейти в директорию $BOT_DIR"
    exit 1
}
echo "📦 Проверка Python..."
if ! command -v python3 &> /dev/null; then
    echo "❌ ОШИБКА: python3 не найден!"
    exit 1
fi
PYTHON_VERSION=$(python3 --version | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "✅ Python версия: $PYTHON_VERSION"
if [ ! -d "$VENV_DIR" ]; then
    echo "📁 Создание виртуального окружения..."
    python3 -m venv "$VENV_DIR" || {
        echo "❌ ОШИБКА: Не удалось создать venv"
        exit 1
    }
    echo "✅ Виртуальное окружение создано"
else
    echo "✅ Виртуальное окружение уже существует"
fi
echo "🔧 Активация виртуального окружения..."
source "$VENV_DIR/bin/activate" || {
    echo "❌ ОШИБКА: Не удалось активировать venv"
    exit 1
}
echo "⬆️  Обновление pip..."
pip install --upgrade pip --quiet || {
    echo "⚠️  Предупреждение: Не удалось обновить pip, продолжаю..."
}
echo "📥 Установка зависимостей..."
echo "   Это может занять несколько минут..."
export OPENBLAS_NUM_THREADS=1
export OMP_NUM_THREADS=1
echo "   Установка torch CPU-only версии (может занять время)..."
if ! pip install --no-cache-dir torch==2.0.1+cpu --index-url https://download.pytorch.org/whl/cpu 2>/dev/null; then
    echo "   Попытка установить torch без CUDA..."
    if ! pip install --no-cache-dir torch==2.0.1 --index-url https://download.pytorch.org/whl/cpu 2>/dev/null; then
        echo "   Попытка установить torch из PyPI (CPU)..."
        if ! pip install --no-cache-dir torch==2.0.1 2>/dev/null; then
            echo "   ⚠️  Не удалось установить torch, продолжаю с остальными зависимостями..."
        else
            echo "   ✅ torch установлен из PyPI"
        fi
    else
        echo "   ✅ torch установлен через альтернативный источник"
    fi
else
    echo "   ✅ torch CPU-only установлен"
fi
echo "   Установка остальных зависимостей..."
if ! pip install --no-cache-dir -r requirements.txt; then
    echo ""
    echo "❌ ОШИБКА: Не удалось установить зависимости"
    echo ""
    echo "Попробуйте установить вручную:"
    echo "  cd $BOT_DIR"
    echo "  source venv/bin/activate"
    echo "  pip uninstall -y torch torchvision torchaudio"
    echo "  pip install torch==2.0.1+cpu --index-url https://download.pytorch.org/whl/cpu"
    echo "  pip install -r requirements.txt"
    exit 1
fi
echo ""
echo "✅ Все зависимости успешно установлены!"
echo ""
echo "📋 Проверка установки..."
python -c "import aiogram; import torch; import silero; print('✅ Основные модули работают')" 2>/dev/null || {
    echo "⚠️  Предупреждение: Не все модули прошли проверку, но установка завершена"
}
echo ""
echo "🎉 Установка завершена!"
echo ""
echo "Следующие шаги:"
echo "  1. Убедитесь, что файл .env настроен правильно"
echo "  2. Запустите бота: ~/www/questguiderf.ru/bot/start.sh"
echo "     или вручную:"
echo "     cd $BOT_DIR/bot"
echo "     source ../venv/bin/activate"
echo "     export OPENBLAS_NUM_THREADS=1"
echo "     export OMP_NUM_THREADS=1"
echo "     export PYTHONPATH=\"\$(pwd)/..:\${PYTHONPATH}\""
echo "     python -m bot.main"
echo ""