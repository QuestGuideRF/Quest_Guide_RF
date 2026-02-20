# QuestGuideRF Online

Веб-версия платформы квестов-экскурсий QuestGuideRF. Полноценный сайт, заменяющий Telegram-бота для прохождения маршрутов: вход по токену из бота, те же маршруты и БД.

**Домен:** https://questguiderf.online

---

## Возможности

- **Вход по токену** — пользователь получает ссылку в боте (`/web`) и входит без пароля
- **Маршруты** — список квестов по городам, карточка маршрута, старт за гроши/баланс
- **Прохождение квеста** — точки с фактами, заданиями (фото / текст / загадка / «Я на месте»), подсказки, аудиогид
- **Загрузка фото** — при задании «фото» загрузка с диска, авто-одобрение
- **Текстовые ответы** — проверка ответа по вариантам из БД (поддержка частичного совпадения)
- **Дашборд** — баланс, статистика, активные квесты с прогресс-баром
- **Банк** — баланс грошей, история покупок
- **Фото** — галерея загруженных фото с фильтром по маршруту
- **Достижения, сертификаты, отзывы** — просмотр своих данных
- **Партнёрка** — реферальная ссылка
- **Настройки** — тема (светлая/тёмная/авто), публичный/скрытый профиль, загрузка аватара
- **Язык** — RU/EN, переключение с редиректом

---

## Требования

- PHP 7.4+ (рекомендуется 8.x)
- MySQL 5.7+ / MariaDB 10.3+
- Расширения: PDO, pdo_mysql, mbstring, json, fileinfo, gd (опционально для аватаров)

Используется **та же БД**, что и у Telegram-бота (таблицы `users`, `user_sessions`, `routes`, `points`, `tasks`, `user_progress`, `user_photos`, `token_balances`, `payments` и др.).

---

## Установка

1. **Скопировать файлы** на хостинг (в отдельную папку или поддомен).

2. **Создать `.env`** в корне (по образцу ниже):

```env
DB_HOST=localhost
DB_NAME=u3403708_QuestGuideFR
DB_USER=u3403708_QuestGuideFR
DB_PASS=ваш_пароль

BOT_TOKEN=...
BOT_USERNAME=questguiderf_bot

SITE_URL=https://questguiderf.online
DEBUG=false

UPLOAD_PATH=../uploads
UPLOAD_URL=/uploads

# Папка для фото квестов (если отличается от UPLOAD_PATH)
# Абсолютный путь — например /www/questguiderf.ru/photos
# В этом случае фото отдаются через /api/serve_photo.php
PHOTOS_PATH=/www/questguiderf.ru/photos
PHOTOS_URL=/uploads
```

3. **Права на запись:**
   - Папка `uploads/` (если используется локальный путь — создаётся автоматически при первой загрузке фото/аватара)
   - Сессии PHP (стандартно через `session.save_path`)

4. **В боте** команда `/web` должна выдавать ссылку вида:
   `https://questguiderf.online/auth/telegram.php?token=XXX`

---

## Структура проекта

```
questguiderf_online/
├── .env                 # конфигурация (не в git)
├── .htaccess            # защита, при необходимости rewrite
├── index.php            # главная / форма входа
├── dashboard.php        # личный кабинет
├── routes.php           # список маршрутов
├── routes/view.php      # карточка маршрута (id)
├── bank.php             # баланс, история покупок
├── photos.php           # галерея фото пользователя
├── achievements.php     # достижения
├── certificates.php     # сертификаты
├── reviews.php          # отзывы
├── partner.php          # партнёрская программа
├── settings.php         # настройки, тема, профиль, аватар
├── logout.php           # выход
├── robots.txt
├── sitemap.xml
├── auth/
│   └── telegram.php     # вход по токену из user_sessions
├── api/
│   ├── upload_avatar.php
│   ├── update_privacy.php
│   └── serve_photo.php   # отдача фото из PHOTOS_PATH (когда папка вне веб-корня)
├── quest/
│   ├── start.php        # старт квеста (списание, создание user_progress)
│   ├── point.php        # страница текущей точки
│   ├── next.php         # переход к следующей точке / завершение
│   ├── complete.php    # экран «Квест завершён»
│   └── api/
│       ├── upload_photo.php
│       ├── check_answer.php
│       └── complete_point.php
├── includes/
│   ├── init.php
│   ├── config.php       # .env, константы
│   ├── db.php           # PDO
│   ├── auth.php         # сессии, requireAuth, getCurrentUser
│   ├── functions.php   # e(), getBalance, getLocalizedField, getUserPhotos, resolvePath
│   ├── i18n.php         # t(), getCurrentLanguage, setLanguage
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/main.js, theme.js
└── favicons/
```

---

## Основные настройки (.env)

| Переменная    | Описание |
|---------------|----------|
| `DB_HOST`     | Хост MySQL |
| `DB_NAME`     | Имя БД (общая с ботом) |
| `DB_USER` / `DB_PASS` | Доступ к БД |
| `BOT_TOKEN`   | Токен бота (для ссылки /web) |
| `BOT_USERNAME`| Юзернейм бота |
| `SITE_URL`    | Базовый URL сайта (без слэша в конце) |
| `DEBUG`       | `true` / `false` |
| `UPLOAD_PATH` | Путь к папке загрузок (относительно корня сайта или абсолютный) |
| `UPLOAD_URL`  | URL для доступа к загрузкам (например `/uploads`) |
| `PHOTOS_PATH`| Путь для фото квестов (по умолчанию = UPLOAD_PATH). Абсолютный путь `/www/questguiderf.ru/photos` — фото отдаются через `/api/serve_photo.php` |
| `PHOTOS_URL` | URL для доступа к фото (по умолчанию = UPLOAD_URL) |

Если `UPLOAD_PATH` / `PHOTOS_PATH` недоступен для записи, фото сохраняются в `uploads/` внутри корня сайта.

---

## Индексация

- **robots.txt** — разрешены главная, дашборд, маршруты, банк, достижения и т.д.; закрыты `/includes/`, `/api/`, `/auth/`, `/quest/api/`, служебные файлы.
- **sitemap.xml** — перечислены публичные/основные страницы с приоритетами и `changefreq`. URL в sitemap зашиты под `https://questguiderf.online`; при другом домене нужно поправить `Sitemap:` в robots.txt и адреса в sitemap.xml.

---

## Безопасность

- Не коммитить `.env` в репозиторий
- Токены входа одноразовые (создаются ботом, срок действия ограничен)
- Загрузка фото: только изображения (MIME), лимит на тип и размер при необходимости задаётся в коде
- Для продакшена: `DEBUG=false`, HTTPS, корректные права на папки