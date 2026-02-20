#!/bin/bash
BOT_DIR="$HOME/www/questguiderf.ru/bot"
echo "🔄 Переустановка torch в CPU-only режиме..."
echo ""
cd "$BOT_DIR" || {
    echo "❌ ОШИБКА: Не удалось перейти в директорию $BOT_DIR"
    exit 1
}
if [ ! -d "venv" ]; then
    echo "❌ ОШИБКА: venv не найден!"
    exit 1
fi
source venv/bin/activate || {
    echo "❌ ОШИБКА: Не удалось активировать venv"
    exit 1
}
echo "🗑️  Удаление старой версии torch..."
pip uninstall -y torch torchvision torchaudio 2>/dev/null || true
echo "📥 Установка torch CPU-only версии..."
pip install --no-cache-dir torch==2.0.1+cpu --index-url https://download.pytorch.org/whl/cpu || {
    echo "Попытка установить torch без версии +cpu..."
    pip install --no-cache-dir torch==2.0.1 --index-url https://download.pytorch.org/whl/cpu || {
        echo "❌ ОШИБКА: Не удалось установить torch"
        exit 1
    }
}
echo ""
echo "✅ torch переустановлен в CPU-only режиме!"
echo ""
echo "Проверка установки..."
python -c "import torch; print(f'✅ torch версия: {torch.__version__}'); print(f'✅ CUDA доступна: {torch.cuda.is_available()}')" || {
    echo "⚠️  Предупреждение: torch установлен, но проверка не прошла"
}
echo ""
echo "🎉 Готово! Теперь можно запустить бота."