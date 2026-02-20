
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
--
-- Структура таблицы `achievements`
--
CREATE TABLE `achievements` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '?',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Общие',
  `order` int UNSIGNED NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Скрытое достижение',
  `condition_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип условия',
  `condition_value` int DEFAULT NULL COMMENT 'Значение условия',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `achievements`
--
INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `category`, `order`, `is_hidden`, `condition_type`, `condition_value`, `created_at`, `updated_at`) VALUES
(1, 'Первые шаги', 'Завершите свой первый маршрут', '🎯', 'Прогресс', 1, 0, 'routes_completed', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(2, 'Исследователь', 'Пройдите 5 маршрутов', '🗺️', 'Прогресс', 2, 0, 'routes_completed', 5, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(3, 'Мастер квестов', 'Пройдите 10 маршрутов', '🏆', 'Прогресс', 3, 0, 'routes_completed', 10, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(4, 'Коллекционер точек', 'Посетите 50 точек', '📍', 'Прогресс', 4, 0, 'points_completed', 50, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(5, 'Фотограф', 'Сделайте 100 фотографий', '📸', 'Активность', 5, 0, 'photos_taken', 100, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(6, 'Перфекционист', 'Пройдите маршрут на 100%', '💯', 'Качество', 6, 0, 'perfect_route', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(7, 'Быстрый', 'Завершите маршрут быстрее времени', '⚡', 'Челленджи', 7, 0, 'fast_completion', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(8, 'Ночной странник', 'Пройдите квест ночью (22:00-06:00)', '🌙', 'Челленджи', 8, 1, 'night_quest', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(9, 'Ранняя пташка', 'Начните квест до 8 утра', '🌅', 'Челленджи', 9, 1, 'early_bird', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(10, 'Легенда', 'Получите все достижения', '👑', 'Особые', 10, 1, 'all_achievements', 9, '2026-01-04 05:51:28', '2026-01-04 05:51:28');
-- --------------------------------------------------------
--
-- Структура таблицы `audio_cache`
--
CREATE TABLE `audio_cache` (
  `id` int UNSIGNED NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ru',
  `text_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MD5 хеш текста для кеширования',
  `audio_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int UNSIGNED DEFAULT NULL COMMENT 'Размер файла в байтах',
  `duration_seconds` int UNSIGNED DEFAULT NULL COMMENT 'Длительность аудио в секундах',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Время истечения кеша'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `audit_log`
--
CREATE TABLE `audit_log` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID администратора, который внес изменение',
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип сущности (route, point, city, etc.)',
  `entity_id` int UNSIGNED NOT NULL COMMENT 'ID измененной сущности',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Действие (create, update, delete)',
  `old_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Старые данные (JSON)',
  `new_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Новые данные (JSON)',
  `changes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание изменений',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `certificates`
--
CREATE TABLE `certificates` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `progress_id` int UNSIGNED NOT NULL,
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ru',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `cities`
--
CREATE TABLE `cities` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание на английском',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `cities`
--
INSERT INTO `cities` (`id`, `name`, `name_en`, `description`, `description_en`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Москва', 'Moscow', 'Москва 🔥 Главный мегаполис страны, который никогда не берет паузу. Это город бесконечных возможностей, где история пишется в режиме реального времени. Здесь амбиции превращаются в рекорды, а старина встречается с будущим на каждом перекрестке. Если хочешь почувствовать пульс страны — он здесь.', 'Moscow 🔥 The main metropolis of the country, which never takes a break. This is a city of endless possibilities where history is written in real time. Here ambitions turn into records, and the past meets the future at every crossroads. If you want to feel the pulse of the country, it is here.', 1, '2026-01-20 19:28:04', '2026-01-20 19:30:34');
-- --------------------------------------------------------
--
-- Структура таблицы `hints`
--
CREATE TABLE `hints` (
  `id` int UNSIGNED NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `level` tinyint UNSIGNED NOT NULL COMMENT '1=легкая, 2=средняя, 3=детальная',
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Текст подсказки',
  `text_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Текст подсказки на английском',
  `has_map` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Есть ли мини-карта',
  `map_image_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к изображению карты',
  `image_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к фото подсказки',
  `order` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Порядок показа',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `hints`
--
INSERT INTO `hints` (`id`, `point_id`, `level`, `text`, `text_en`, `has_map`, `map_image_path`, `image_path`, `order`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'В Александровском саду, рядом с Кремлёвской стеной', 'In the Alexander Garden, next to the Kremlin wall', 0, '', '', 0, '2026-01-21 14:15:06', '2026-01-31 00:56:07'),
(2, 1, 2, 'Высокий светлый обелиск с фамилиями', 'Tall light obelisk with surnames', 0, NULL, NULL, 1, '2026-01-21 14:21:33', '2026-01-31 00:56:15'),
(3, 1, 3, 'Стоит между Манежем и Могилой Неизвестного солдата', 'Stands between the Manege and the Tomb of the Unknown Soldier', 0, NULL, NULL, 0, '2026-01-21 14:21:49', '2026-01-31 00:56:18'),
(4, 2, 1, 'Иди вдоль Кремлёвской стены.', 'Walk along the Kremlin wall.', 0, NULL, NULL, 0, '2026-01-21 15:54:30', '2026-01-31 00:56:22'),
(5, 2, 2, 'Бронзовая звезда и солдатский пост.', 'Bronze Star and Soldier\'s Post.', 0, NULL, NULL, 0, '2026-01-21 15:54:30', '2026-01-31 00:56:26'),
(6, 2, 3, 'Пламя горит в центре пятиконечной звезды.', 'A flame burns in the center of a five-pointed star.', 0, NULL, NULL, 0, '2026-01-21 15:54:30', '2026-01-31 00:56:29'),
(7, 3, 1, 'Выйди на Манежную площадь.', 'Go out to Manezhnaya Square.', 0, NULL, NULL, 0, '2026-01-21 15:55:10', '2026-01-31 00:56:33'),
(8, 3, 2, 'Перед большим красным зданием.', 'In front of the big red building.', 0, NULL, NULL, 0, '2026-01-21 15:55:10', '2026-01-31 00:56:37'),
(9, 3, 3, 'Бронзовый всадник на коне', 'Bronze rider on horseback', 0, NULL, NULL, 0, '2026-01-21 15:55:10', '2026-01-31 00:56:41'),
(10, 4, 1, 'Встань лицом к Жукову.', 'Stand facing Zhukov.', 0, NULL, NULL, 0, '2026-01-21 15:55:31', '2026-01-31 00:56:45'),
(11, 4, 2, 'Чуть левее — красная арка с башнями.', 'A little to the left is a red arch with towers.', 0, NULL, NULL, 0, '2026-01-21 15:55:31', '2026-01-31 00:56:51'),
(12, 4, 3, 'Между проходами — часовня.', 'Between the aisles is a chapel.', 0, NULL, NULL, 0, '2026-01-21 15:55:31', '2026-01-31 00:57:17'),
(13, 5, 1, ' Сразу слева после арки.', 'Immediately to the left after the arch.', 0, NULL, NULL, 0, '2026-01-21 15:55:58', '2026-01-31 00:58:50'),
(14, 5, 2, 'Небольшая красно-белая церковь.', 'A small red and white church.', 0, NULL, NULL, 0, '2026-01-21 15:55:58', '2026-01-31 00:58:50'),
(15, 5, 3, 'Купол виден с Красной площади.', 'The dome is visible from Red Square.', 0, NULL, NULL, 0, '2026-01-21 15:55:58', '2026-01-31 00:58:50'),
(16, 6, 1, ' Длинное здание с витринами слева.', 'Long building with storefronts on the left.', 0, NULL, NULL, 0, '2026-01-21 15:56:21', '2026-01-31 00:58:50'),
(17, 6, 2, 'Иди до конца фасада.', 'Go to the end of the facade.', 0, NULL, NULL, 0, '2026-01-21 15:56:21', '2026-01-31 00:58:50'),
(18, 6, 3, 'Развернись спиной к Красной площади.', '', 0, NULL, NULL, 0, '2026-01-21 15:56:21', '2026-01-31 00:58:50'),
(19, 7, 1, 'Встань левым плечом к зданию.', 'Stand with your left shoulder facing the building.', 0, NULL, NULL, 0, '2026-01-21 15:56:59', '2026-01-31 00:58:50'),
(20, 7, 2, 'Иди прямо до арки.', 'Go straight to the arch.', 0, NULL, NULL, 0, '2026-01-21 15:56:59', '2026-01-31 00:58:50'),
(21, 7, 3, 'Пройди сквозь неё насквозь.', 'Go right through it.', 0, NULL, NULL, 0, '2026-01-21 15:56:59', '2026-01-31 00:58:50'),
(22, 8, 1, 'Левым плечом к ЦДМ.', 'Left shoulder to the CDM.', 0, NULL, NULL, 0, '2026-01-21 15:58:01', '2026-01-31 00:58:50'),
(23, 8, 2, 'Метро «Лубянка» по правую руку.', 'Metro \"Lubyanka\" on the right.', 0, NULL, NULL, 0, '2026-01-21 15:58:01', '2026-01-31 00:58:50'),
(24, 8, 3, 'Розовая церковь у дороги.', 'Pink church by the road.', 0, NULL, NULL, 0, '2026-01-21 15:58:01', '2026-01-31 00:58:50'),
(25, 9, 1, 'Иди прямо по улице.', 'Walk straight down the street.', 0, NULL, NULL, 0, '2026-01-21 15:58:22', '2026-01-31 00:58:50'),
(26, 9, 2, 'Большая красная «М».', 'Big red \"M\".', 0, NULL, NULL, 0, '2026-01-21 15:58:22', '2026-01-31 01:32:35'),
(27, 9, 3, 'Под ней цветные полосы (фиолетовый и оранжевый — вход 2).', 'Below it are colored stripes (purple and orange - input 2).', 0, NULL, NULL, 0, '2026-01-21 15:58:22', '2026-01-31 01:32:35'),
(28, 10, 1, 'Спустись в подземный переход.', 'Go down into the underground passage.', 0, NULL, NULL, 0, '2026-01-21 15:58:55', '2026-01-31 01:32:35'),
(29, 10, 2, 'Выход №4.', 'Exit No. 4.', 0, NULL, NULL, 0, '2026-01-21 15:58:55', '2026-01-31 01:32:35'),
(30, 10, 3, 'Чугунная часовня в сквере.', 'Cast iron chapel in the park.', 0, NULL, NULL, 0, '2026-01-21 15:58:55', '2026-01-31 01:32:35'),
(31, 11, 1, 'Иди вниз по склону.', 'Go down the hill.', 0, NULL, NULL, 0, '2026-01-21 15:59:20', '2026-01-31 01:32:35'),
(32, 11, 2, 'В конце сквера памятник.', 'At the end of the square there is a monument.', 0, NULL, NULL, 0, '2026-01-21 15:59:25', '2026-01-31 01:32:35'),
(33, 11, 3, 'Два мужчины с книгой и крестом.', 'Two men with a book and a cross.', 0, NULL, NULL, 0, '2026-01-21 15:59:25', '2026-01-31 01:32:35'),
(34, 12, 1, 'Перейди дорогу.', 'Cross the road.', 0, NULL, NULL, 0, '2026-01-21 15:59:43', '2026-01-21 15:59:43'),
(35, 12, 2, 'Красная кирпичная церковь.', 'Red brick church.', 0, NULL, NULL, 0, '2026-01-21 15:59:43', '2026-01-31 01:34:36'),
(36, 12, 3, ' Храм Всех Святых.', 'Church of All Saints.', 0, NULL, NULL, 0, '2026-01-21 15:59:43', '2026-01-31 01:34:36'),
(37, 13, 1, ' Двигайся в сторону реки.', 'Move towards the river.', 0, NULL, NULL, 0, '2026-01-21 16:00:10', '2026-01-31 01:34:36'),
(38, 13, 2, 'Широкая прогулочная зона у воды.', 'Wide walking area by the water.', 0, NULL, NULL, 0, '2026-01-21 16:00:10', '2026-01-31 01:34:36'),
(39, 13, 3, 'Каменная набережная с видом на Кремль.', 'Stone embankment overlooking the Kremlin.', 0, NULL, NULL, 0, '2026-01-21 16:00:10', '2026-01-31 01:34:36'),
(40, 14, 1, 'Иди вдоль кирпичной стены.', 'Walk along the brick wall.', 0, NULL, NULL, 0, '2026-01-21 16:00:36', '2026-01-31 01:34:36'),
(41, 14, 2, 'Выйди к набережной.', 'Go out to the embankment.', 0, NULL, NULL, 0, '2026-01-21 16:01:35', '2026-01-31 01:34:36'),
(42, 14, 3, 'Лестница к стеклянному зданию.\r\n', 'Staircase to a glass building.', 0, NULL, NULL, 0, '2026-01-21 16:01:35', '2026-01-31 01:34:36'),
(43, 15, 1, 'Иди к бетонному мосту.', 'Go to the concrete bridge.', 0, NULL, NULL, 0, '2026-01-21 16:02:42', '2026-01-31 01:34:36'),
(44, 15, 2, ' Он нависает над рекой.', 'It hangs over the river.', 0, NULL, NULL, 0, '2026-01-21 16:02:42', '2026-01-31 01:34:36'),
(45, 15, 3, ' Самая дальняя точка — над Москвой-рекой.', 'The farthest point is above the Moscow River.', 0, NULL, NULL, 0, '2026-01-21 16:02:42', '2026-01-31 01:34:36'),
(46, 16, 1, 'Встань спиной к реке.', 'Stand with your back to the river.', 0, NULL, NULL, 0, '2026-01-21 16:03:09', '2026-01-31 01:34:36'),
(47, 16, 2, 'Найди жёлтое здание у перекрёстка.', 'Find the yellow building at the intersection.', 0, NULL, NULL, 0, '2026-01-21 16:03:09', '2026-01-21 16:03:09'),
(48, 16, 3, ' Пройди между жёлтым домом и храмом.', 'Walk between the yellow house and the temple.', 0, NULL, NULL, 0, '2026-01-21 16:03:09', '2026-01-21 16:03:09'),
(49, 17, 1, 'Спуск к собору от парка.', 'Descent to the cathedral from the park.', 0, NULL, NULL, 0, '2026-01-21 16:03:34', '2026-01-21 16:03:34'),
(50, 17, 2, 'Красно-белый храм с разноцветными куполами.', 'Red and white temple with multi-colored domes.', 0, NULL, NULL, 0, '2026-01-21 16:03:34', '2026-01-21 16:03:34'),
(51, 17, 3, 'Памятник двум мужчинам перед входом.', 'Monument to two men in front of the entrance.', 0, NULL, NULL, 0, '2026-01-21 16:03:34', '2026-01-21 16:03:34'),
(52, 18, 1, 'Иди прямо по пешеходной улице.', 'Walk straight along the pedestrian street.', 0, NULL, NULL, 0, '2026-01-21 16:04:37', '2026-01-21 16:04:37'),
(53, 18, 2, 'Проход слева, затем поворот направо.\r\n', 'Pass on the left, then turn right.', 0, NULL, NULL, 0, '2026-01-21 16:04:37', '2026-01-21 16:04:37'),
(54, 18, 3, 'Дом с мифическим зверем над фасадом.\r\n', 'House with a mythical beast above the facade.', 0, NULL, NULL, 0, '2026-01-21 16:04:37', '2026-01-21 16:04:37'),
(55, 19, 1, 'Упрёшься в широкую дорогу.', 'You will run into a wide road.', 0, NULL, NULL, 0, '2026-01-21 16:05:00', '2026-01-21 16:05:00'),
(56, 19, 2, 'Поверни направо.', 'Turn right.', 0, NULL, NULL, 0, '2026-01-21 16:05:00', '2026-01-21 16:05:00'),
(57, 19, 3, ' Огромное здание с часами.', 'A huge building with a clock.', 0, NULL, NULL, 0, '2026-01-21 16:05:00', '2026-01-21 16:05:00');
-- --------------------------------------------------------
--
-- Структура таблицы `moderation_tasks`
--
CREATE TABLE `moderation_tasks` (
  `id` int UNSIGNED NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип задачи (photo, review, etc.)',
  `entity_id` int UNSIGNED NOT NULL COMMENT 'ID сущности',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_to` int UNSIGNED DEFAULT NULL COMMENT 'ID администратора, которому назначена задача',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `payments`
--
CREATE TABLE `payments` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `amount` int UNSIGNED NOT NULL COMMENT 'Рубли',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RUB',
  `status` enum('PENDING','SUCCESS','FAILED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `telegram_payment_charge_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_payment_charge_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `points`
--
CREATE TABLE `points` (
  `id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `order` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `address` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fact_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Факт/легенда',
  `fact_text_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Факт на английском',
  `require_pose` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'hands_up, heart, point',
  `min_people` int UNSIGNED NOT NULL DEFAULT '1',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Бесплатная демо-точка',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `audio_enabled` tinyint(1) DEFAULT '0' COMMENT 'Включен ли аудиогид для точки',
  `audio_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к аудиофайлу',
  `audio_language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ru' COMMENT 'Язык аудио (ru, en, de и т.д.)',
  `audio_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Текст для озвучки аудиогида',
  `audio_text_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Текст для озвучки на английском',
  `audio_file_path_ru` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к аудиофайлу (русский)',
  `audio_file_path_en` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к аудиофайлу (английский)',
  `task_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo' COMMENT 'photo, text, riddle',
  `text_answer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Правильный ответ для текстовых заданий',
  `text_answer_hint` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Подсказка к ответу',
  `accept_partial_match` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Принимать частичное совпадение',
  `max_attempts` int UNSIGNED NOT NULL DEFAULT '3' COMMENT 'Максимум попыток для текстового ответа'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `points`
--
INSERT INTO `points` (`id`, `route_id`, `order`, `name`, `name_en`, `address`, `fact_text`, `fact_text_en`, `require_pose`, `min_people`, `latitude`, `longitude`, `is_free`, `created_at`, `updated_at`, `audio_enabled`, `audio_file_path`, `audio_language`, `audio_text`, `audio_text_en`, `audio_file_path_ru`, `audio_file_path_en`, `task_type`, `text_answer`, `text_answer_hint`, `accept_partial_match`, `max_attempts`) VALUES
(1, 1, 1, '📍 Точка №1 Александровский Сад', '📍 Point No. 1 Alexander Garden', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ Этот обелиск — настоящий «хамелеон» истории. Его поставили в 1914 году в честь 300-летия дома Романовых, и на камне были выбиты имена царей. Но после Революции большевики не снесли памятник, а переделали его! Имена царей стесали, а вместо них выбили имена революционеров-мыслителей: Маркса, Энгельса, Плеханова. Только в 2013 году памятнику вернули его первоначальный вид. Если приглядеться, можно заметить, что камень немного неровный там, где стачивали старые надписи.\r\n', 'HISTORICAL FACT This obelisk is a real “chameleon” of history. It was erected in 1914 in honor of the 300th anniversary of the Romanov dynasty, and the names of the kings were carved on the stone. But after the Revolution, the Bolsheviks did not demolish the monument, but remade it! The names of the tsars were erased, and in their place the names of revolutionary thinkers were knocked out: Marx, Engels, Plekhanov. Only in 2013 the monument was restored to its original appearance. If you look closely, you can see that the stone is a little uneven where the old inscriptions were ground down.', '', 1, 55.75370000, 37.61485300, 0, '2026-01-20 16:34:15', '2026-01-21 16:22:32', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\n1. Станции метро: «Александровский сад», «Библиотека им. Ленина» или «Охотный ряд».\r\n2. Выходи к Историческому музею и Манежной площади.\r\n3. Найди главные чугунные ворота в Александровский сад.\r\n\r\n👣 Куда идти:\r\n\r\n1. Заходи в сад через главные ворота и иди по аллее прямо.\r\n2. Вечный огонь и Кремлевская стена будут у тебя по левую руку.\r\n3. Пройди мимо Поста №1 буквально 50–70 метров вглубь сада.\r\n4. Справа от дорожки ищи серый каменный столб с золотым орлом.\r\n\r\n🎯 Твоя цель:\r\nРОМАНОВСКИЙ ОБЕЛИСК\r\n(Подойди к нему вплотную)\r\n\r\n--------------------------------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '🚇 How to get there:\r\n\r\n1. Metro stations: “Alexandrovsky Sad”, “Biblioteka im. Lenin\" or \"Okhotny Ryad\".\r\n2. Go to the Historical Museum and Manezhnaya Square.\r\n3. Find the main cast-iron gate to the Alexander Garden.\r\n\r\n👣Where to go:\r\n\r\n1. Enter the garden through the main gate and go straight along the alley.\r\n2. The eternal flame and the Kremlin wall will be on your left hand.\r\n3. Walk past Post No. 1 literally 50–70 meters deep into the garden.\r\n4. To the right of the path, look for a gray stone pillar with a golden eagle.\r\n\r\n🎯 Your goal:\r\nROMANOVSKY OBELISK\r\n(Get close to him)\r\n\r\n--------------------------------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', '', '', 'text', '', '', 1, 3),
(2, 1, 2, '📍 Точка №2 Вечный огонь (Могила Неизвестного Солдата)', '📍 Point No. 2 Eternal Flame (Tomb of the Unknown Soldier)', NULL, '📜 ИСТОРИЧЕСКИЙ ФАКТ:\r\n\r\n1.🔥 Этот огонь горит здесь непрерывно с 1967 года.\r\n\r\n2.🚛 Его зажгли от факела, доставленного на бронетранспортере из самого Ленинграда (с Марсова поля).\r\n\r\n3.💂 Интересно, что каждые три часа здесь происходит торжественная смена караула — это «Пост №1».\r\n\r\n4.👞 Если повезет, ты увидишь их знаменитый «печатный шаг», когда нога поднимается параллельно земле.\r\n', '📜 HISTORICAL FACT:\r\n\r\n1.🔥 This fire has been burning here continuously since 1967.\r\n\r\n2.🚛 It was lit from a torch delivered on an armored personnel carrier from Leningrad itself (from the Field of Mars).\r\n\r\n3.💂 Interestingly, every three hours there is a ceremonial changing of the guard here - this is “Post No. 1”.\r\n\r\n4.👞 If you\'re lucky, you\'ll see their famous \"print step\" where the leg rises parallel to the ground.', '', 1, 55.75477500, 37.61609900, 0, '2026-01-20 16:54:27', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n1. 🚶 Встань спиной к Романовскому обелиску и возвращайся назад к выходу из сада.\r\n\r\n2. 🏰 Теперь Кремлевская стена будет у тебя по правую руку.\r\n\r\n3. 🔥 Через 50 метров ты увидишь Почетный караул и неугасающее пламя у подножия стены.\r\n\r\n4. 🎖️ Это главный военный мемориал страны.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель:\r\nМОГИЛА НЕИЗВЕСТНОГО СОЛДАТА\r\n\r\n(Подойди к центральной части мемориала, где горит огонь)\r\n', '👣Where to go:\r\n\r\n1. 🚶 Stand with your back to the Romanovsky Obelisk and go back to the exit from the garden.\r\n\r\n2. 🏰 Now the Kremlin wall will be on your right hand.\r\n\r\n3. 🔥 After 50 meters you will see the Guard of Honor and the unquenchable flame at the foot of the wall.\r\n\r\n4. 🎖️ This is the main war memorial of the country.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal:\r\nTOMB OF THE UNKNOWN SOLDIER\r\n\r\n(Go to the central part of the memorial, where the fire is burning)', NULL, NULL, 'photo', '', '', 1, 3),
(3, 1, 3, '📍 Точка №3 Памятник великому полководцу', '📍 Point No. 3 Monument to the great commander', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n👤 Существует легенда, что Сталин сам хотел принимать Парад Победы верхом, но во время репетиции конь его сбросил.\r\n\r\n🎖️ Тогда он поручил это почетное дело своему лучшему полководцу.\r\n\r\n🐎 Конь по кличке «Кумир», на котором сидит всадник, действительно существовал.\r\n\r\n🔍 Его очень долго искали по всей стране, чтобы он был идеально белым и статным.', 'HISTORICAL FACT\r\n\r\n👤 There is a legend that Stalin himself wanted to take part in the Victory Parade on horseback, but during the rehearsal his horse threw him off.\r\n\r\n🎖️ Then he entrusted this honorable task to his best commander.\r\n\r\n🐎 The horse named “Idol”, on which the rider sits, really existed.\r\n\r\n🔍 They were looking for him all over the country for a very long time so that he would be perfectly white and stately.', '', 1, 55.75579600, 37.61690800, 0, '2026-01-21 13:14:49', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n1.🚶 Продолжаем путь! От Вечного огня иди к выходу из Александровского сада (к тем самым чугунным воротам, через которые ты входил).\r\n\r\n2.🏰 Кремлевская стена и Вечный огонь теперь должны оставаться у тебя по правую руку.\r\n\r\n3.🚩 Выходи из ворот на Манежную площадь. Прямо перед тобой — монументальное красное здание Исторического музея.\r\n\r\n4.🏇 А перед его фасадом ты увидишь величественный памятник человеку на коне.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ПАМЯТНИК ПОЛКОВОДЦУ. (Подойди к подножию памятника).', '👣Where to go:\r\n\r\n1.🚶 Let\'s continue our journey! From the Eternal Flame, go to the exit from the Alexander Garden (to the same cast-iron gate through which you entered).\r\n\r\n2.🏰 The Kremlin wall and the Eternal Flame should now remain at your right hand.\r\n\r\n3.🚩 Exit the gate to Manezhnaya Square. Directly in front of you is the monumental red building of the Historical Museum.\r\n\r\n4.🏇 And in front of its facade you will see a majestic monument to a man on a horse.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: MONUMENT TO THE COMMANDER. (Go to the foot of the monument).', NULL, NULL, 'photo', '', '', 1, 3),
(4, 1, 4, '📍 Точка №4 Нулевой километр автодорог России', '📍 Point No. 4 Zero kilometer of Russian roads', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n📍 Хотя этот знак называется «Нулевой километр», на самом деле он чисто символический.\r\n\r\n📏 Настоящий географический нулевой километр России находится в паре сотен метров отсюда — у здания Центрального телеграфа на Тверской улице.\r\n\r\n🍀 Но именно здесь, у ворот, всегда толпятся туристы, пытаясь «поймать удачу за хвост» и забросить монетку в центр круга.', '💡 HISTORICAL FACT\r\n\r\n📍 Although this sign is called “Kilometer Zero”, it is actually purely symbolic.\r\n\r\n📏 The real geographical zero kilometer of Russia is located a couple of hundred meters from here - near the Central Telegraph building on Tverskaya Street.\r\n\r\n🍀 But it is here, at the gate, that tourists always crowd, trying to “catch luck by the tail” and throw a coin into the center of the circle.', '', 1, 55.75564800, 37.61796400, 0, '2026-01-21 13:23:17', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🚶 Встань лицом к памятнику полководцу, которого ты только что отгадал, и посмотри налево.\r\n\r\n🏰 Ты увидишь красивые красные ворота с двумя остроконечными шпилями — это Воскресенские ворота, вход на Красную площадь.\r\n\r\n📍 Тебе нужно подойти к ним: прямо перед воротами, в проезде, ты увидишь вмонтированный в брусчатку блестящий бронзовый знак.\r\n\r\n✨ Это магическое место, где начинается отсчет всех дорог страны.\r\n\r\n--------------------------------------------------------------\r\n🎯 Твоя цель: НУЛЕВОЙ КИЛОМЕТР\r\n\r\n(Встань в самый центр знака)', '👣Where to go:\r\n\r\n🚶 Stand facing the monument to the commander you just guessed and look to the left.\r\n\r\n🏰 You will see a beautiful red gate with two pointed spiers - this is the Resurrection Gate, the entrance to Red Square.\r\n\r\n📍 You need to approach them: right in front of the gate, in the driveway, you will see a shiny bronze sign built into the paving stones.\r\n\r\n✨ This is a magical place where the countdown of all the roads in the country begins.\r\n\r\n--------------------------------------------------------------\r\n🎯 Your goal: ZERO KILOMETER\r\n\r\n(Stand in the very center of the sign)', NULL, NULL, 'text', '', '', 1, 3),
(5, 1, 5, '📍 Точка №5 Казанский собор', '📍 Point No. 5 Kazan Cathedral', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🐦 Этот собор — настоящий «феникс». В 1936 году по приказу Сталина его полностью снесли, чтобы освободить место для прохода военной техники во время парадов.\r\n\r\n🏚️ На месте святыни сначала построили павильон в честь III Интернационала, а позже там и вовсе был общественный туалет.\r\n\r\n📐 Однако архитектор Пётр Барановский перед сносом успел тайно сделать точные замеры здания.\r\n\r\n🏗️ Спустя полвека, в 1990-х годах, собор стал первым храмом в Москве, который восстановили из небытия по тем самым чертежам.', '💡 HISTORICAL FACT\r\n\r\n🐦 This cathedral is a real “phoenix”. In 1936, by order of Stalin, it was completely demolished to make room for the passage of military equipment during parades.\r\n\r\n🏚️ At the site of the shrine, a pavilion was first built in honor of the Third International, and later there was a public toilet there.\r\n\r\n📐 However, the architect Pyotr Baranovsky managed to secretly take accurate measurements of the building before the demolition.\r\n\r\n🏗️ Half a century later, in the 1990s, the cathedral became the first temple in Moscow to be restored from oblivion according to the same drawings.', '', 1, 55.75527000, 37.61890900, 0, '2026-01-21 13:32:47', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти: \r\n\r\n🚶От Нулевого километра пройди сквозь Воскресенские ворота. Поздравляю, ты на Красной площади!\r\n\r\n🏰 Как только выйдешь из-под арки ворот, сразу посмотри налево.\r\n\r\n🍭 Ты увидишь очень нарядный, «пряничный» красно-белый храм с золотыми куполами.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: КАЗАНСКИЙ СОБОР.', '👣Where to go: \r\n\r\n🚶From the Zero Kilometer, go through the Resurrection Gate. Congratulations, you are on Red Square!\r\n\r\n🏰 As soon as you come out from under the gate arch, immediately look to the left.\r\n\r\n🍭 You will see a very elegant, “gingerbread” red and white temple with golden domes.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: KAZAN CATHEDRAL.', NULL, NULL, 'photo', '', '', 1, 3),
(6, 1, 6, '📍 Точка №6: ГУМ (Главный Универсальный Магазин)', '📍 Point No. 6: GUM (Main Department Store)', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n    \r\n🏗️ Главная гордость ГУМа — его легендарная стеклянная крыша. Её спроектировал инженер Владимир Шухов (тот самый, что построил Шуховскую телебашню).\r\n\r\n⚖️ Конструкция кажется легкой и воздушной, но на самом деле на неё ушло более 800 тонн стали!\r\n\r\n☀️ Она спроектирована так, чтобы выдерживать огромные массы снега и при этом пропускать максимум солнечного света, чтобы внутри всегда было светло, как на улице.', '💡 HISTORICAL FACT\r\n    \r\n🏗️ GUM’s main pride is its legendary glass roof. It was designed by engineer Vladimir Shukhov (the same one who built the Shukhov TV tower).\r\n\r\n⚖️ The design seems light and airy, but in fact it took more than 800 tons of steel!\r\n\r\n☀️ It is designed to withstand huge amounts of snow and at the same time let in maximum sunlight, so that it is always as bright inside as it is outside.', '', 1, 55.75530300, 37.61958600, 0, '2026-01-21 13:43:48', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🚶 От Казанского собора просто пройди несколько десятков метров вдоль Красной площади.\r\n\r\n🏛️ ГУМ невозможно пропустить — это монументальное здание с башенками и нарядными окнами, которое тянется по всей левой стороне площади.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: Центральный вход, обращенный к Мавзолею к Казанскому Собору)', '👣Where to go:\r\n\r\n🚶 From the Kazan Cathedral, just walk a few tens of meters along Red Square.\r\n\r\n🏛️ GUM is impossible to miss - it is a monumental building with turrets and elegant windows that stretches along the entire left side of the square.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: The central entrance facing the Mausoleum to the Kazan Cathedral)', NULL, NULL, 'photo', '', '', 1, 3),
(7, 1, 7, '📍 Точка №7: Печатный двор (Никольская, 15)', '📍 Point No. 7: Printing Yard (Nikolskaya, 15)', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n📖 Колыбель книгопечатания: Именно здесь в 1564 году Иван Фёдоров напечатал первую точно датированную русскую книгу — «Апостол».\r\n\r\n☀️ Древние технологии: Обрати внимание на солнечные часы — они до сих пор исправны! В солнечный день по тени от металлического штыря можно проверить время.\r\n\r\n🕰️ Важный нюанс: Время будет «древнемосковским», без учета современных часовых поясов, так что с часами на смартфоне оно может не совпасть!', '💡 HISTORICAL FACT\r\n\r\n📖 Cradle of book printing: It was here in 1564 that Ivan Fedorov printed the first accurately dated Russian book, “The Apostle.”\r\n\r\n☀️ Ancient technologies: Pay attention to the sundial - it is still working! On a sunny day, you can check the time by the shadow of the metal pin.\r\n\r\n🕰️ Important nuance: The time will be “ancient Moscow”, without taking into account modern time zones, so it may not coincide with the clock on your smartphone!', '', 1, 55.75737400, 37.62248600, 0, '2026-01-21 13:50:42', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🔄 Встань спиной к Красной площади (и к ГУМу) и начинай движение прямо по Никольской улице. Это та самая улица, которая круглый год украшена яркими «небесными» гирляндами.\r\n\r\n🚶 Иди прямо. Проходи мимо входа в метро «Площадь Революции» (он будет по левую руку).\r\n\r\n🏰 Продолжай идти, пока по левой стороне не увидишь необычное здание в готическом стиле: с небесно-голубыми стенами, белыми колоннами и острыми шпилями.\r\n\r\n📢 Подсказка «Звонок другу»: Если совсем запутались, спросите у любого прохожего: «Подскажите, а где здесь здание Историко-архивного института (РГГУ) или старый Печатный двор?»\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ПЕЧАТНЫЙ ДВОР\r\n\r\n(Остановись у фасада с большими солнечными часами)', '👣Where to go:\r\n\r\n🔄 Stand with your back to Red Square (and to GUM) and start moving straight along Nikolskaya Street. This is the same street that is decorated with bright “heavenly” garlands all year round.\r\n\r\n🚶 Go straight. Walk past the entrance to the Ploshchad Revolyutsii metro station (it will be on your left).\r\n\r\n🏰 Continue walking until you see an unusual Gothic-style building on the left side: with sky-blue walls, white columns and sharp spiers.\r\n\r\n📢 Hint “Call a friend”: If you are completely confused, ask any passerby: “Tell me, where is the building of the Historical and Archival Institute (RGGU) or the old Printing Yard?”\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: PRINTING YARD\r\n\r\n(Stop by the façade with the big sundial)', NULL, NULL, 'photo', '', '', 1, 3),
(8, 1, 8, '📍 Точка №8: Третьяковский проезд', '📍 Point No. 8: Tretyakovsky passage', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🖼️ Братья Третьяковы были не только ценителями искусства, но и очень расчетливыми бизнесменами.\r\n\r\n🏗️ Чтобы окупить строительство этой «прорухи» в крепостной стене Китай-города, они построили по бокам здания специально для аренды магазинов.\r\n\r\n💎 Сейчас это место называют «самым дорогим тупиком Москвы», хотя на самом деле это полноценный проезд. Здесь сосредоточены бутики самых роскошных мировых брендов.', '💡 HISTORICAL FACT\r\n\r\n🖼️ The Tretyakov brothers were not only connoisseurs of art, but also very prudent businessmen.\r\n\r\n🏗️ To pay for the construction of this “hole” in the fortress wall of Kitay-Gorod, they built buildings on the sides specifically for renting shops.\r\n\r\n💎 Now this place is called “the most expensive dead end in Moscow,” although in fact it is a full-fledged passage. Boutiques of the world\'s most luxurious brands are concentrated here.', '', 1, 55.75863600, 37.62341700, 0, '2026-01-21 13:55:57', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти: \r\n\r\n🚶Встань левым плечом к зданию Печатного двора (где ты нашел Единорога) и иди дальше по Никольской улице.\r\n\r\n🏰 Совсем скоро слева ты увидишь огромную каменную арку, похожую на вход в средневековый замок.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ПРОЙТИ В АРКУ \r\n', '👣Where to go: \r\n\r\n🚶Stand with your left shoulder to the Printing Yard building (where you found the Unicorn) and walk further along Nikolskaya Street.\r\n\r\n🏰 Very soon you will see a huge stone arch on the left, similar to the entrance to a medieval castle.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: GO TO THE ARCH', NULL, NULL, 'photo', '', '', 1, 3),
(9, 1, 9, '📍 Точка №9: Объект на Лубянской площади', '📍 Point No. 9: Object on Lubyanka Square', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🎈 Подарок молодежи: Это здание было построено в 1957 году специально к Всемирному фестивалю молодежи и студентов.\r\n\r\n🏰 Сказочный вид: Архитектор Алексей Душкин спроектировал эти невероятные окна-арки, чтобы огромное здание выглядело светлым, легким и по-настоящему волшебным.\r\n\r\n🕰️ Гигантский механизм: Внутри находятся одни из крупнейших в мире механических часов. Они весят около 5 тонн и состоят из тысяч деталей. Их запустили специально к открытию здания после большой реконструкции.', '💡 HISTORICAL FACT\r\n\r\n🎈 Gift to Youth: This building was built in 1957 specifically for the World Festival of Youth and Students.\r\n\r\n🏰 Fairy Tale View: Architect Alexey Dushkin designed these incredible arched windows to make the huge building look light, light and truly magical.\r\n\r\n🕰️Giant Movement: Inside is one of the world\'s largest mechanical watches. They weigh about 5 tons and consist of thousands of parts. They were launched specifically for the opening of the building after a major reconstruction.', '', 1, 55.75943000, 37.62502900, 0, '2026-01-21 13:59:11', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🏰 Выходи из Третьяковского проезда через арку к большой дороге (Театральный проезд).\r\n\r\n↗️ Поверни направо и иди вверх вдоль дороги.\r\n\r\n🏛️ Совсем скоро на противоположной стороне улицы ты увидишь монументальное здание, занимающее целый квартал. Оно выделяется огромными арочными окнами.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: Перейти дорогу и подойти к главному входу этого здания.', '👣Where to go:\r\n\r\n🏰 Exit Tretyakovsky Proezd through the arch to the main road (Teatralny Proezd).\r\n\r\n↗️ Turn right and go up along the road.\r\n\r\n🏛️ Very soon on the opposite side of the street you will see a monumental building occupying an entire block. It stands out with huge arched windows.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: Cross the road and approach the main entrance of this building.', NULL, NULL, 'text', '', '', 1, 3),
(10, 1, 10, '📍 Точка №10: Иоанн Богослов под Вязом', '📍 Point No. 10: John the Evangelist under the Elm', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🏺 Город-слоеный пирог: Этот храм — один из ярких примеров того, как Москва буквально наслаивается сама на себя. Разные даты на его стенах — это следы пожаров, масштабных перестроек, периодов забвения и возвращения к жизни.\r\n\r\n🌳 Название «под Вязом»: Это живое напоминание о временах, когда главными ориентирами в городе служили не станции метро, навигаторы или точные адреса, а одно-единственное дерево — огромный вяз, росший здесь несколько столетий назад.\r\n\r\n🏫 Не только церковь: В советское время в этом здании располагался Музей истории Москвы, и только недавно оно снова обрело свой первоначальный статус.', '💡 HISTORICAL FACT\r\n\r\n🏺 Layer Cake City: This temple is one of the striking examples of how Moscow literally layers itself on top of itself. Various dates on its walls are traces of fires, large-scale reconstruction, periods of oblivion and return to life.\r\n\r\n🌳 The name “Under the Elm”: This is a living reminder of the times when the main landmarks in the city were not metro stations, navigators or exact addresses, but a single tree - a huge elm that grew here several centuries ago.\r\n\r\n🏫 Not only the church: During Soviet times, this building housed the Moscow History Museum, and only recently it regained its original status.', '', 1, 55.75759000, 37.62782400, 0, '2026-01-21 14:03:21', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🏢 Встань лицом к зданию ЦДМ. Ты находишься на нужной стороне улицы (на той же, где сейчас стоишь, переходить дорогу обратно не нужно).\r\n\r\n🚶 Иди прямо, не сворачивая. По пути ты пройдёшь мимо углового здания с большими витринами и ироничной вывеской, из которой понятно, что здесь не подают то, что обычно плавает 🐟 — это твой верный ориентир.\r\n\r\n🚇 Продолжай движение всё время прямо. Ты пройдёшь мимо входа в метро «Лубянская площадь», просто оставив его по пути.\r\n\r\n⛪ Через несколько десятков метров ты увидишь небольшую старинную церковь, стоящую прямо на линии улицы, без сквера и ограды — будто она случайно уцелела между современными домами.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ХРАМ ИОАННА БОГОСЛОВА ПОД ВЯЗОМ', '👣Where to go:\r\n\r\n🏢 Stand facing the CDM building. You are on the right side of the street (on the same side where you are now standing; there is no need to cross the road back).\r\n\r\n🚶 Walk straight without turning. On the way, you will pass by a corner building with large shop windows and an ironic sign, from which it is clear that they do not serve what usually floats here 🐟 - this is your sure guide.\r\n\r\n🚇 Keep moving straight all the time. You will pass by the entrance to the Lubyanka Square metro station, simply leaving it along the way.\r\n\r\n⛪ After a few tens of meters you will see a small ancient church standing right on the street line, without a park or fence - as if it had accidentally survived between modern houses.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: THE TEMPLE OF JOHN THE GOLDEN UNDER THE ELM', NULL, NULL, 'photo', '', '', 1, 3),
(11, 1, 11, '📍 Точка №11: Метро «Китай-город»', '📍 Point No. 11: Metro “Kitay-Gorod”', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🚉 Уникальная станция: «Китай-город» — одна из немногих в мире пересадок кросс-платформенного типа. Это значит, что поезда разных линий приходят на одну платформу. Чтобы пересесть, не нужно бегать по длинным переходам — достаточно просто перейти на другую сторону зала.\r\n\r\n🎨 Визуальный код: Цветные полосы под буквой «М» на входе — это «язык» метрополитена. Они придуманы для того, чтобы ты сразу понял, на какие ветки попадешь, еще до того, как спустишься вниз и заглянешь в карту.', '💡 HISTORICAL FACT\r\n\r\n🚉 Unique station: “Kitai-Gorod” is one of the few cross-platform transfers in the world. This means that trains from different lines arrive at the same platform. To change seats, you don’t need to run along long passages - you just need to go to the other side of the hall.\r\n\r\n🎨 Visual code: The colored stripes under the letter “M” at the entrance are the “language” of the metro. They were invented so that you immediately understand which branches you will end up on, even before you go down and look at the map.', '', 1, 55.75666700, 37.62944100, 0, '2026-01-21 14:07:33', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n⛪ Оставь розовый храм позади и осмотрись вокруг. Твоя следующая цель находится под землёй, но искать вход специально не нужно — город сам подскажет дорогу.\r\n\r\n📉 Иди прямо по Новой площади в сторону понижения рельефа (улица уходит немного вниз).\r\n\r\n🏮 Ищи знакомую каждому москвичу красную букву «М». Она — твой маяк.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: Вход в метро «Китай-город».\r\n\r\n✋ Стой! Остановись прямо перед буквой «М», но не спеши спускаться — ответ на следующее задание находится снаружи.', '👣Where to go:\r\n\r\n⛪ Leave the pink temple behind and look around. Your next goal is underground, but you don’t need to specifically look for the entrance - the city itself will show you the way.\r\n\r\n📉 Walk straight along New Square towards the lower relief (the street goes down a little).\r\n\r\n🏮 Look for the red letter “M”, familiar to every Muscovite. She is your beacon.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: Entrance to the Kitay-Gorod metro station.\r\n\r\n✋ Stop! Stop right before the letter \"M\", but don\'t rush down - the answer to the next task is outside.', NULL, NULL, 'photo', '', '', 1, 3),
(12, 1, 12, '📍 Точка №12: Часовня-памятник «Героям Плевны»', '📍 Point No. 12: Chapel-monument to the “Heroes of Plevna”', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🦎 Странное прозвище: В народе этот величественный памятник называют забавным именем — «У Хвоста».\r\n\r\n🚌 Откуда взялся хвост? Всё просто: в советское время прямо здесь находилась конечная остановка автобусов и маршруток. Очередь пассажиров была настолько длинной, что огибала памятник, напоминая огромный чешуйчатый хвост.\r\n\r\n🔑 Городской пароль: Фраза «Встретимся у хвоста» на десятилетия стала кодовым паролем для встреч нескольких поколений москвичей.', '💡 HISTORICAL FACT\r\n\r\n🦎 Strange nickname: People call this majestic monument by a funny name - “At the Tail”.\r\n\r\n🚌Where did the tail come from? It\'s simple: in Soviet times, right here was the final stop for buses and minibuses. The line of passengers was so long that it wrapped around the monument, resembling a huge scaly tail.\r\n\r\n🔑 City password: The phrase “Meet me at the tail” for decades became the code password for meetings of several generations of Muscovites.', '', 1, 55.75669400, 37.63118900, 0, '2026-01-21 14:10:23', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти: \r\n\r\n🚇Пришло время ненадолго спуститься под землю. Заходи в переход метро. Внизу, в этом каменном лабиринте, поверни направо.\r\n\r\n🔀 Ищи глазами указатель «Выход №4». Поднимайся по лестнице — и ты окажешься прямо в начале Ильинского сквера.\r\n\r\n🗼 Перед тобой вырастет высокая чёрная башня необычной формы.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ПАМЯТНИК ГЕРОЯМ ПЛЕВНЫ.\r\n\r\n(Подойди вплотную к этой чугунной часовне)', '👣Where to go: \r\n\r\n🚇It\'s time to go underground for a while. Enter the subway passage. Down in this stone maze, turn right.\r\n\r\n🔀 Look for the “Exit No. 4” sign. Climb the stairs and you will find yourself right at the beginning of Ilyinsky Square.\r\n\r\n🗼 A tall black tower of an unusual shape will rise in front of you.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: MONUMENT TO THE HEROES OF PLEVNA.\r\n\r\n(Come close to this cast iron chapel)', NULL, NULL, 'photo', '', '', 1, 3),
(13, 1, 13, '📍 Точка №13: Кирилл и Мефодий', '📍 Point No. 13: Cyril and Methodius', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🕯️ Символ знаний: У подножия памятника горит Неугасимая лампада. Она символизирует свет знаний и просвещения, который несут в мир книги и грамота.\r\n\r\n📅 Главный праздник: Каждый год 24 мая, в День славянской письменности и культуры, именно отсюда начинается масштабный крестный ход и большой городской праздник.\r\n\r\n🎓 Студенческая традиция: А раньше, еще до революции, у московских студентов было поверье: нужно прийти к братьям-просветителям и попросить удачи перед сложными экзаменами.', '💡 HISTORICAL FACT\r\n\r\n🕯️ Symbol of knowledge: An unquenchable lamp burns at the foot of the monument. It symbolizes the light of knowledge and enlightenment that books and literacy bring to the world.\r\n\r\n📅 Main holiday: Every year on May 24, on the Day of Slavic Literature and Culture, this is where a large-scale religious procession and a big city holiday begin.\r\n\r\n🎓 Student tradition: And earlier, even before the revolution, Moscow students had a belief: you need to come to the enlightenment brothers and ask for good luck before difficult exams.', '', 1, 55.75459300, 37.63392900, 0, '2026-01-21 14:13:09', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n⛪ Оставь черную часовню за спиной и иди вниз по главной аллее Ильинского сквера. Дорожка будет вести тебя под уклон.\r\n\r\n📜 Через пару минут ты увидишь перед собой величественный памятник двум старцам в монашеских рясах. В руках у них — высокий крест и развернутый свиток.\r\n\r\n📖 Это братья-просветители, подарившие нам славянскую азбуку.', '👣Where to go:\r\n\r\n⛪ Leave the black chapel behind you and go down the main alley of Ilyinsky Square. The path will lead you downhill.\r\n\r\n📜 In a couple of minutes you will see in front of you a majestic monument to two elders in monastic robes. In their hands are a high cross and an unfolded scroll.\r\n\r\n📖 These are the enlightenment brothers who gave us the Slavic alphabet.', NULL, NULL, 'photo', '', '', 1, 3),
(14, 1, 14, '📍 Точка №14: Церковь Всех Святых на Кулишках', '📍 Point No. 14: Church of All Saints on Kulishki', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n👹 «У черта на куличках»: Ты наверняка слышал это выражение, когда говорят о чем-то очень далеком. Так вот — ты пришел в самое сердце тех самых «куличек»!\r\n\r\n🌲 Что такое кулишки? Раньше так называли болотистые места или вырубки в лесу. В XVII веке в этой церкви, по легенде, завелся беспокойный дух (полтергейст), который пугал прихожан и швырял вещи.\r\n\r\n🗣️ Рождение поговорки: Именно с тех пор пошла фраза про черта, который поселился на Кулишках. А «далеким» это место стало казаться позже, когда Москва разрослась, и окраинные Кулишки стали восприниматься как край света.', '💡 HISTORICAL FACT\r\n\r\n👹 “In the middle of nowhere”: You\'ve probably heard this expression when they talk about something very distant. So - you have come to the very heart of those very “little cakes”!\r\n\r\n🌲 What are kulishki? Previously, this was the name given to swampy places or clearings in the forest. In the 17th century, in this church, according to legend, a restless spirit (poltergeist) started up, which scared the parishioners and threw things.\r\n\r\n🗣️ The birth of a saying: It was from then that the phrase about the devil who settled in Kulishki began. And this place began to seem “far away” later, when Moscow grew, and the outlying Kulishki began to be perceived as the end of the world.', '', 1, 55.75365900, 37.63494000, 0, '2026-01-21 14:15:23', '2026-01-21 16:22:32', 1, NULL, 'ru', '👣 Куда идти:\r\n\r\n🌲 Спускайся до самого конца Ильинского сквера к площади Варварские Ворота.\r\n\r\n🚶 Перейди дорогу по пешеходному переходу к высокому зданию из красного кирпича с выразительной колокольней.\r\n\r\n⛪ Это один из самых старых храмов Москвы, стены которого помнят события многовековой давности.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Твоя цель: ХРАМ ВСЕХ СВЯТЫХ НА КУЛИШКАХ\r\n\r\n(Встань так, чтобы тебе была хорошо видна колокольня)', '👣Where to go:\r\n\r\n🌲 Go down to the very end of Ilyinsky Square to Varvarskie Vorota Square.\r\n\r\n🚶 Cross the road at the pedestrian crossing to a tall red brick building with an expressive bell tower.\r\n\r\n⛪ This is one of the oldest churches in Moscow, the walls of which remember the events of centuries ago.\r\n\r\n--------------------------------------------------------------\r\n\r\n🎯 Your goal: TEMPLE OF ALL SAINTS ON KULISHKI\r\n\r\n(Stand so that you can clearly see the bell tower)', NULL, NULL, 'photo', '', '', 1, 3),
(15, 1, 15, '📍 Точка №15 НАБЕРЕЖНАЯ (ПАРК «ЗАРЯДЬЕ»)', '📍 Point No. 15 EMBANKMENT (ZARYADYE PARK)', NULL, '💡 Интересный факт:\r\n\r\nНа месте Зарядья до 2006 года стояла гостиница «Россия» — одна из крупнейших в мире. Это был настоящий «город в городе» — более 3000 номеров, огромный кинотеатр, концертный зал и даже отдельный пост милиции.\r\n\r\nКогда её решили снести, обломков бетона и арматуры хватило бы на строительство целого жилого микрорайона!', '💡 Interesting fact:\r\n\r\nUntil 2006, on the site of Zaryadye stood the Rossiya Hotel, one of the largest in the world. It was a real “city within a city” - more than 3,000 rooms, a huge cinema, a concert hall and even a separate police post.\r\n\r\nWhen they decided to demolish it, the fragments of concrete and reinforcement would have been enough to build an entire residential neighborhood!', '', 1, 55.74970300, 37.63253400, 0, '2026-01-21 15:14:45', '2026-01-21 16:22:32', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\nОт церкви Всех Святых — в сторону реки.\r\n\r\n👣 Куда идти:\r\n\r\n1. Оставь церковь Всех Святых позади.\r\n2. Двигайся в сторону реки.\r\n3. Выходи на широкую прогулочную зону у воды.\r\n\r\n🎯 Твоя цель:\r\nКАМЕННАЯ НАБЕРЕЖНАЯ\r\n(Найди точку с видом на Кремль)\r\n\r\n---------------------------------------------\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]\r\n', '🚇 How to get there:\r\n\r\nFrom All Saints Church - towards the river.\r\n\r\n👣Where to go:\r\n\r\n1. Leave All Saints Church behind.\r\n2. Move towards the river.\r\n3. Go out to a wide walking area near the water.\r\n\r\n🎯 Your goal:\r\nSTONE EMBANKMENT\r\n(Find a point with a view of the Kremlin)\r\n\r\n---------------------------------------------\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(16, 1, 16, '📍 Точка №16 ЗАРЯДЬЕ (СТУПЕНИ)', '📍 Point No. 16 CHARGE (STAGES)', NULL, '💡 Интересный факт:\r\n\r\nАрхитектура парка «Зарядье» повторяет природные зоны России. Здесь можно увидеть тундру, степь, лес и болото — всё в одном месте в центре Москвы!', '💡 Interesting fact:\r\n\r\nThe architecture of Zaryadye Park follows the natural areas of Russia. Here you can see the tundra, steppe, forest and swamp - all in one place in the center of Moscow!', '', 1, 55.75052000, 37.63144100, 0, '2026-01-21 15:16:04', '2026-01-24 01:59:14', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\nОт набережной — к стеклянному зданию.\r\n\r\n👣 Куда идти:\r\n\r\n1. Встань на набережной лицом к Москве-реке.\r\n2. Поверни направо.\r\n3. Двигайся по пешеходной дорожке, к большому стеклянному зданию, не поднимаясь вверх.\r\n4. Подойди вплотную к этому зданию — у его основания, со стороны набережной, находится большая лестница со ступенями.\r\n\r\n🎯 Твоя цель:\r\nБОЛЬШАЯ ЛЕСТНИЦА В ПАРКЕ «ЗАРЯДЬЕ»\r\n(Встань у основания лестницы)\r\n\r\n--------------------------------------------------------------\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '🚇 How to get there:\r\n\r\nFrom the embankment to the glass building.\r\n\r\n👣Where to go:\r\n\r\n1. Stand on the embankment facing the Moscow River.\r\n2. Turn right.\r\n3. Follow the walkway towards the large glass building without going up.\r\n4. Come close to this building - at its base, on the embankment side, there is a large staircase with steps.\r\n\r\n🎯 Your goal:\r\nGREAT STAIRWAY IN ZARYADYE PARK\r\n(Stand at the bottom of the stairs)\r\n\r\n--------------------------------------------------------------\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(17, 1, 17, '📍 Точка №17 ПАРЯЩИЙ МОСТ (ПАРК «ЗАРЯДЬЕ»)', '📍 Point No. 17 FLOATING BRIDGE (ZARYADYE PARK)', NULL, '💡 Интересный факт:\r\n\r\nТрудно поверить, но на всём этом огромном пространстве, где сейчас холмы и сады «Зарядья», до 2006 года стояла гостиница «Россия».\r\n\r\nЭто был настоящий «город в городе» — одна из крупнейших гостиниц в мире (более 3000 номеров). В ней был свой огромный кинотеатр, концертный зал и даже отдельный пост милиции.\r\n', '💡 Interesting fact:\r\n\r\nIt’s hard to believe, but in this entire vast space, where the hills and gardens of Zaryadye are now, until 2006 there was the Rossiya Hotel.\r\n\r\nIt was a real “city within a city” - one of the largest hotels in the world (more than 3,000 rooms). It had its own huge cinema, concert hall and even a separate police post.', '', 1, 55.74942800, 37.62946700, 0, '2026-01-21 15:38:36', '2026-01-24 02:01:42', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\nОт ступеней — к бетонному мосту над рекой.\r\n\r\n👣 Куда идти:\r\n\r\n1. Поднявшись по лестнице, остановись наверху.\r\n2. Прямо перед тобой будет Парящий мост.\r\n3. Поверни направо и иди по дорожке к центру моста.\r\n4. Выйди на мост и двигайся до его самой дальней точки.\r\n\r\n🎯 Твоя цель:\r\nПАРЯЩИЙ МОСТ\r\n(Дойди до моста и поднимись на его самую высокую точку)\r\n\r\n--------------------------------------------------------------\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]\r\n', '🚇 How to get there:\r\n\r\nFrom the steps to the concrete bridge over the river.\r\n\r\n👣Where to go:\r\n\r\n1. After going up the stairs, stop at the top.\r\n2. There will be a Floating Bridge right in front of you.\r\n3. Turn right and follow the path to the center of the bridge.\r\n4. Get out onto the bridge and move to its farthest point.\r\n\r\n🎯 Your goal:\r\nFLOATING BRIDGE\r\n(Go to the bridge and climb to its highest point)\r\n\r\n--------------------------------------------------------------\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(18, 1, 18, '📍 Точка №18 КРАСНАЯ ПЛОЩАДЬ, 5 (СРЕДНИЕ ТОРГОВЫЕ РЯДЫ)', '📍 Point No. 18 RED SQUARE, 5 (MIDDLE TRADE ROWS)', NULL, '💡 Интересный факт:\r\n\r\nЖёлтое здание — бывшие торговые ряды, часть исторической застройки у Кремля. Эти ряды были построены в конце XIX века и служили главным торговым центром Москвы.', '💡 Interesting fact:\r\n\r\nThe yellow building is a former shopping arcade, part of the historical buildings near the Kremlin. These rows were built at the end of the 19th century and served as the main shopping center of Moscow.', '', 1, 55.75124400, 37.62679600, 0, '2026-01-21 15:39:45', '2026-01-21 16:22:32', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\nОт Парящего моста — в сторону Красной площади.\r\n\r\n👣 Куда идти:\r\n\r\n1. Встань спиной к реке.\r\n2. Найди жёлтое здание у перекрёстка.\r\n3. Пройди между жёлтым домом и храмом.\r\n\r\n🎯 Твоя цель:\r\nЖЁЛТОЕ ЗДАНИЕ (СРЕДНИЕ ТОРГОВЫЕ РЯДЫ)\r\n(Подойди к зданию)\r\n\r\n--------------------------------------------------------------\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]\r\n', '🚇 How to get there:\r\n\r\nFrom the Floating Bridge - towards Red Square.\r\n\r\n👣Where to go:\r\n\r\n1. Stand with your back to the river.\r\n2. Find the yellow building at the intersection.\r\n3. Walk between the yellow house and the temple.\r\n\r\n🎯 Your goal:\r\nYELLOW BUILDING (MIDDLE TRADE RANKS)\r\n(Come to the building)\r\n\r\n--------------------------------------------------------------\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(19, 1, 19, '📍 Точка №19 КРАСНАЯ ПЛОЩАДЬ И СОБОР ВАСИЛИЯ БЛАЖЕННОГО (ФИНАЛ)', '📍 Point No. 19 RED SQUARE AND ST. BASILY\'S CATHEDRAL (FINAL)', NULL, '💡 Интересный факт:\r\n\r\nСобор Василия Блаженного на самом деле состоит из 11 церквей, объединённых одним основанием.\r\n\r\nЛегенда гласит, что Иван Грозный приказал ослепить архитекторов собора, чтобы они больше никогда не смогли построить ничего прекраснее. К счастью, это всего лишь страшная сказка — историки доказали, что мастера после этого строили и другие храмы.\r\n\r\nА памятник Минину и Пожарскому раньше стоял в самом центре Красной площади, прямо напротив ГУМа. Его передвинули к собору только в 1931 году, потому что он мешал проведению военных парадов.\r\n', '💡 Interesting fact:\r\n\r\nSt. Basil\'s Cathedral actually consists of 11 churches united by one foundation.\r\n\r\nLegend has it that Ivan the Terrible ordered the cathedral\'s architects to be blinded so that they would never be able to build anything more beautiful. Fortunately, this is just a terrible fairy tale - historians have proven that the masters built other temples after this.\r\n\r\nAnd the monument to Minin and Pozharsky used to stand in the very center of Red Square, right opposite GUM. It was moved to the cathedral only in 1931 because it interfered with military parades.', '', 1, 55.75202300, 37.62385600, 0, '2026-01-21 15:41:41', '2026-01-21 16:22:32', 1, NULL, 'ru', '🚇 Как добраться:\r\n\r\nОт жёлтого здания — к Собору Василия Блаженного.\r\n\r\n👣 Куда идти:\r\n\r\n1. Находясь на мосту или у набережной, встань спиной к реке.\r\n2. Иди по левому спуску (дорожке), заходя вглубь парка «Зарядье».\r\n3. Двигайся прямо в сторону Красной площади.\r\n4. Выйди к перекрёстку между Собором Василия Блаженного (справа) и Средними торговыми рядами (слева).\r\n5. Проходи вперёд и встань прямо перед фасадом собора.\r\n\r\n🎯 Твоя цель:\r\nСОБОР ВАСИЛИЯ БЛАЖЕННОГО\r\n(Оказаться на Васильевском спуске прямо перед входом в храм)\r\n\r\n--------------------------------------------------------------\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '🚇 How to get there:\r\n\r\nFrom the yellow building - to St. Basil\'s Cathedral.\r\n\r\n👣Where to go:\r\n\r\n1. While on a bridge or near an embankment, stand with your back to the river.\r\n2. Walk along the left descent (path), going deep into Zaryadye Park.\r\n3. Move straight towards Red Square.\r\n4. Go to the intersection between St. Basil\'s Cathedral (on the right) and the Middle Shopping Rows (on the left).\r\n5. Walk forward and stand right in front of the cathedral façade.\r\n\r\n🎯 Your goal:\r\nBASIL\'S CATHEDRAL\r\n(Be on Vasilyevsky Spusk right in front of the entrance to the temple)\r\n\r\n--------------------------------------------------------------\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3);
-- --------------------------------------------------------
--
-- Структура таблицы `promo_codes`
--
CREATE TABLE `promo_codes` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Промокод',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание промокода',
  `discount_type` enum('percentage','fixed','free_route') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage' COMMENT 'Тип скидки: процент, фиксированная сумма, бесплатный маршрут',
  `discount_value` decimal(10,2) DEFAULT NULL COMMENT 'Значение скидки (процент или сумма)',
  `route_id` int UNSIGNED DEFAULT NULL COMMENT 'ID маршрута (для бесплатного маршрута)',
  `max_uses` int UNSIGNED DEFAULT NULL COMMENT 'Максимальное количество использований (NULL = без ограничений)',
  `used_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Количество использований',
  `valid_from` datetime DEFAULT NULL COMMENT 'Действителен с',
  `valid_until` datetime DEFAULT NULL COMMENT 'Действителен до',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Активен',
  `created_by` int UNSIGNED DEFAULT NULL COMMENT 'ID администратора, создавшего промокод',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Промокоды';
-- --------------------------------------------------------
--
-- Структура таблицы `promo_code_uses`
--
CREATE TABLE `promo_code_uses` (
  `id` int UNSIGNED NOT NULL,
  `promo_code_id` int UNSIGNED NOT NULL COMMENT 'ID промокода',
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID пользователя',
  `route_id` int UNSIGNED DEFAULT NULL COMMENT 'ID маршрута (если применен к маршруту)',
  `discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Размер скидки',
  `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время использования'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='История использования промокодов';
-- --------------------------------------------------------
--
-- Структура таблицы `reference_images`
--
CREATE TABLE `reference_images` (
  `id` int UNSIGNED NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `file_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telegram file_id',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь: /uploads/reference/point_X/file.jpg',
  `embedding` blob,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `reviews`
--
CREATE TABLE `reviews` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `progress_id` int UNSIGNED NOT NULL COMMENT 'ID прохождения',
  `rating` tinyint UNSIGNED NOT NULL COMMENT 'Рейтинг 1-5',
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Одобрен администратором',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Скрыт администратором'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `reviews`
--
INSERT INTO `reviews` (`id`, `user_id`, `route_id`, `progress_id`, `rating`, `text`, `created_at`, `updated_at`, `is_approved`, `is_hidden`) VALUES
(1, 2, 1, 1, 4, 'Очень атмосферный маршрут, всё понравилось, но иногда фото распознаются не с первого раза. В целом ок, будем ещё ходить.', '2026-01-28 12:00:00', '2026-01-28 12:00:00', 1, 0),
(2, 3, 1, 2, 5, 'Super experience in the center of Moscow! Tasks are clear, hints are helpful, photos check works great. Recommended!', '2026-01-28 12:05:12', '2026-01-28 12:05:12', 1, 0),
(3, 4, 1, 3, 5, 'Очень крутой формат прогулки, будто играешь в квест в реальном городе. Голосовой гид и факты прям в тему.', '2026-01-28 12:07:48', '2026-01-28 12:07:48', 1, 0),
(4, 5, 1, 4, 5, 'Очень понравилась структура маршрута и заданий. Без багов, проверка фото быстрая, админы отвечают оперативно.', '2026-01-28 12:09:33', '2026-01-28 12:09:33', 1, 0),
(5, 6, 1, 5, 5, 'Прошли маршрут как семейную прогулку. Дети в восторге от заданий и загадок, взрослым тоже было интересно.', '2026-01-28 12:12:05', '2026-01-28 12:12:05', 1, 0),
(6, 7, 1, 6, 5, 'Отличный баланс прогулки, истории и фана. Аудиогид с живым голосом — огромный плюс.', '2026-01-28 12:14:57', '2026-01-28 12:14:57', 1, 0),
(7, 8, 1, 7, 5, 'Отличный способ посмотреть центр Москвы без скучных экскурсий. Квест держит внимание до конца.', '2026-01-28 12:17:21', '2026-01-28 12:17:21', 1, 0),
(8, 9, 1, 8, 5, 'Фото‑задания забавные, проверка работает уверенно. Гида не нужно — бот сам всё ведёт.', '2026-01-28 12:19:59', '2026-01-28 12:19:59', 1, 0),
(9, 10, 1, 9, 5, 'Очень понравился маршрут: продуманные точки, красивые виды, понятные подсказки. 5/5.', '2026-01-28 12:22:44', '2026-01-28 12:22:44', 1, 0),
(10, 11, 1, 10, 5, 'Проходили как тимбилдинг. Всем зашло, особенно сочетание загадок и проверки фото.', '2026-01-28 12:25:31', '2026-01-28 12:25:31', 1, 0),
(11, 12, 1, 11, 5, 'Крутая идея — получать достижения и сертификат за прохождение. Чувствуется завершённый продукт.', '2026-01-28 12:28:17', '2026-01-28 12:28:17', 1, 0),
(12, 13, 1, 12, 5, 'Всё работает плавно: оплаты, подсказки, фото, личный кабинет на сайте. Удобный интерфейс.', '2026-01-28 12:31:09', '2026-01-28 12:31:09', 1, 0),
(13, 14, 1, 13, 5, 'Маршрут сделали вечером после работы, устали, но довольны. Узнали много нового про центр Москвы.', '2026-01-28 12:34:26', '2026-01-28 12:34:26', 1, 0),
(14, 15, 1, 14, 5, 'Понятный вход в квест, инструкции без воды. Бот ведёт шаг за шагом, заблудиться невозможно.', '2026-01-28 12:37:54', '2026-01-28 12:37:54', 1, 0),
(15, 16, 1, 15, 5, 'Очень красиво построены подсказки: сначала лёгкие намёки, потом детальные подсказки. Баланс отличный.', '2026-01-28 12:41:03', '2026-01-28 12:41:03', 1, 0),
(16, 17, 1, 16, 5, 'Круто, что всё внутри Telegram плюс сайт — не нужно ставить отдельные приложения.', '2026-01-28 12:44:39', '2026-01-28 12:44:39', 1, 0),
(17, 18, 1, 17, 5, 'Отличный городской квест: не слишком лёгкий, но и не перегруженный. Идеально для выходного.', '2026-01-28 12:47:52', '2026-01-28 12:47:52', 1, 0),
(18, 19, 1, 18, 5, 'Обработка фото для галереи на сайте — приятный бонус, после квеста картинки смотрятся ещё лучше.', '2026-01-28 12:51:18', '2026-01-28 12:51:18', 1, 0),
(19, 20, 1, 19, 5, 'Всё понравилось: маршрутизация, точки, тексты, голос. Чувствуется внимание к деталям.', '2026-01-28 12:54:42', '2026-01-28 12:54:42', 1, 0),
(20, 21, 1, 20, 5, 'Один из лучших квест‑ботов, что я пробовал. Понятная логика, без багов, приятный дизайн.', '2026-01-28 12:58:09', '2026-01-28 12:58:09', 1, 0);
-- --------------------------------------------------------
--
-- Структура таблицы `routes`
--
CREATE TABLE `routes` (
  `id` int UNSIGNED NOT NULL,
  `city_id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание на английском',
  `route_type` enum('WALKING','CYCLING') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WALKING',
  `price` int UNSIGNED NOT NULL DEFAULT '399' COMMENT 'Цена в рублях',
  `estimated_duration` int UNSIGNED DEFAULT NULL COMMENT 'Минуты',
  `distance` decimal(5,2) DEFAULT NULL COMMENT 'Километры',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `order` int UNSIGNED NOT NULL DEFAULT '0',
  `max_hints_per_route` int UNSIGNED NOT NULL DEFAULT '3' COMMENT 'Максимум подсказок на маршрут',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `difficulty` tinyint UNSIGNED DEFAULT '2' COMMENT '1=легкий, 2=средний, 3=сложный',
  `duration_minutes` int UNSIGNED DEFAULT '60' COMMENT 'Длительность в минутах',
  `age_min` tinyint UNSIGNED DEFAULT NULL COMMENT 'Минимальный возраст',
  `age_max` tinyint UNSIGNED DEFAULT NULL COMMENT 'Максимальный возраст',
  `season` enum('winter','spring','summer','autumn','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'all' COMMENT 'Сезон'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `routes`
--
INSERT INTO `routes` (`id`, `city_id`, `name`, `name_en`, `description`, `description_en`, `route_type`, `price`, `estimated_duration`, `distance`, `is_active`, `order`, `max_hints_per_route`, `created_at`, `updated_at`, `difficulty`, `duration_minutes`, `age_min`, `age_max`, `season`) VALUES
(1, 1, 'Сердце столицы: Сквозь века', 'The Heart of the Capital: Through the Ages', '🏙 Квест-прогулка «Сердце Москвы»\r\n\r\nЧто это? Не скучная экскурсия, а городская игра 🕵️‍♂️. Исторический центр станет вашим полем для исследований. Забудьте про Википедию — все ответы спрятаны в архитектуре и деталях вокруг вас.\r\n👥 ДЛЯ КОГО?\r\n\r\n    👯‍♂️ Друзья и пары — для небанального отдыха.\r\n\r\n    🧑‍💼 Команды — легкий тимбилдинг (2–10 чел).\r\n\r\n    🏠 Местные и туристы — чтобы сказать: «Я был тут сто раз, но этого не видел!»\r\n\r\n    Специальных знаний не нужно. Только внимательность 👀 и азарт.\r\n\r\n📊 ЦИФРЫ\r\n\r\n    ⏱️ Время: ~2 часа.\r\n\r\n    👟 Дистанция: ~5 км (спокойный темп).\r\n\r\n    ☀️ Когда: Строго в светлое время суток (старт до 17:00). Ночью подсказок не видно!\r\n\r\n🧠 ЧТО БУДЕМ ДЕЛАТЬ?\r\n\r\n    🔍 Искать тайные знаки на фасадах.\r\n\r\n    🐉 Ловить мифических существ.\r\n\r\n    🧩 Решать загадки без Гугла.\r\n\r\n    📸 Делать фото в лучших локациях.\r\n\r\n🎒 С СОБОЙ\r\n\r\n    Удобная обувь (много брусчатки!).\r\n\r\n    Заряженный телефон 🔋.\r\n\r\n    Настрой на открытия.\r\n', '🏙 Quest walk “Heart of Moscow”\r\n\r\nWhat is this? Not a boring excursion, but a city game 🕵️‍♂️. The historical center will be your field of exploration. Forget Wikipedia - all the answers are hidden in the architecture and details around you.\r\n👥 FOR WHOM?\r\n\r\n    👯‍♂️ Friends and couples - for a non-trivial vacation.\r\n\r\n    🧑‍💼 Teams - easy team building (2–10 people).\r\n\r\n    🏠 Locals and tourists - to say: “I’ve been here a hundred times, but I haven’t seen this!”\r\n\r\n    No special knowledge required. Only attentiveness 👀 and excitement.\r\n\r\n📊 NUMBERS\r\n\r\n    ⏱️ Time: ~2 hours.\r\n\r\n    👟 Distance: ~5 km (calm pace).\r\n\r\n    ☀️ When: Strictly during daylight hours (start before 17:00). You can\'t see the clues at night!\r\n\r\n🧠 WHAT SHALL WE DO?\r\n\r\n    🔍 Look for secret signs on facades.\r\n\r\n    🐉 Catch mythical creatures.\r\n\r\n    🧩 Solve riddles without Google.\r\n\r\n    📸 Take photos in the best locations.\r\n\r\n🎒 WITH YOU\r\n\r\n    Comfortable shoes (lots of cobblestones!).\r\n\r\n    Charged phone 🔋.\r\n\r\n    The mood for discovery.', 'WALKING', 399, 130, NULL, 1, 0, 3, '2026-01-20 19:31:18', '2026-01-21 18:11:19', 2, 60, NULL, NULL, 'all');
-- --------------------------------------------------------
--
-- Структура таблицы `route_tags`
--
CREATE TABLE `route_tags` (
  `id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `route_tags`
--
INSERT INTO `route_tags` (`id`, `route_id`, `tag_id`, `created_at`) VALUES
(1, 1, 2, '2026-01-21 17:31:20'),
(2, 1, 3, '2026-01-21 17:31:20'),
(3, 1, 1, '2026-01-21 17:31:20'),
(4, 1, 5, '2026-01-21 17:31:20'),
(5, 1, 4, '2026-01-21 17:31:20'),
(6, 1, 6, '2026-01-21 17:31:20'),
(7, 1, 13, '2026-01-21 17:31:20'),
(8, 1, 11, '2026-01-21 17:31:20'),
(9, 1, 12, '2026-01-21 17:31:20'),
(10, 1, 15, '2026-01-21 17:31:20'),
(11, 1, 14, '2026-01-21 17:31:20'),
(12, 1, 17, '2026-01-21 17:31:20'),
(13, 1, 21, '2026-01-21 17:31:20'),
(14, 1, 22, '2026-01-21 17:31:20'),
(15, 1, 24, '2026-01-21 17:31:20'),
(16, 1, 23, '2026-01-21 17:31:20'),
(17, 1, 27, '2026-01-21 17:31:20'),
(18, 1, 25, '2026-01-21 17:31:20'),
(19, 1, 26, '2026-01-21 17:31:20');
-- --------------------------------------------------------
--
-- Структура таблицы `system_settings`
--
CREATE TABLE `system_settings` (
  `id` int UNSIGNED NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `system_settings`
--
INSERT INTO `system_settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'restart_notifications_enabled', '0', 'Уведомления о перезапуске бота (1 - включено, 0 - выключено)', '2026-01-18 12:21:30', '2026-01-18 12:22:47');
-- --------------------------------------------------------
--
-- Структура таблицы `tags`
--
CREATE TABLE `tags` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('topic','age','difficulty','duration','season') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Эмодзи или Font Awesome класс',
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HEX цвет для отображения',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `tags`
--
INSERT INTO `tags` (`id`, `name`, `name_en`, `slug`, `type`, `icon`, `color`, `created_at`) VALUES
(1, 'История', 'History', 'istoriya', 'topic', '🏛️', '#8B4513', '2026-01-09 08:26:10'),
(2, 'Архитектура', 'Architecture', 'arhitektura', 'topic', '🏗️', '#4682B4', '2026-01-09 08:26:10'),
(3, 'Искусство', 'Art', 'iskusstvo', 'topic', '🎨', '#FF69B4', '2026-01-09 08:26:10'),
(4, 'Развлечения', 'Entertainment', 'razvlecheniya', 'topic', '🎢', '#FF6347', '2026-01-09 08:26:10'),
(5, 'Природа', 'Nature', 'priroda', 'topic', '🌳', '#228B22', '2026-01-09 08:26:10'),
(6, 'Религия', 'Religion', 'religiya', 'topic', '⛪', '#9370DB', '2026-01-09 08:26:10'),
(7, 'Спорт', 'Sport', 'sport', 'topic', '⚽', '#FF8C00', '2026-01-09 08:26:10'),
(8, 'Ночная жизнь', 'Nightlife', 'nochnaya-zhizn', 'topic', '🌃', '#191970', '2026-01-09 08:26:10'),
(9, 'Еда и рестораны', 'Food and restaurants', 'eda', 'topic', '🍽️', '#DC143C', '2026-01-09 08:26:10'),
(10, 'Шоппинг', 'Shopping', 'shopping', 'topic', '🛍️', '#FFD700', '2026-01-09 08:26:10'),
(11, 'Детские (0-12)', 'Children (0-12)', 'detskie', 'age', '👶', '#FFB6C1', '2026-01-09 08:26:10'),
(12, 'Подростковые (13-17)', 'Teenagers (13-17)', 'podrostkovye', 'age', '👦', '#87CEEB', '2026-01-09 08:26:10'),
(13, 'Взрослые (18+)', 'Adults (18+)', 'vzroslye', 'age', '👨', '#4169E1', '2026-01-09 08:26:10'),
(14, 'Семейные', 'Family', 'semeinye', 'age', '👨‍👩‍👧', '#32CD32', '2026-01-09 08:26:10'),
(15, 'Пожилые (60+)', 'Elderly (60+)', 'pozhilye', 'age', '👴', '#D3D3D3', '2026-01-09 08:26:10'),
(16, 'Легкий', 'Easy', 'legkiy', 'difficulty', '⭐', '#90EE90', '2026-01-09 08:26:10'),
(17, 'Средний', 'Medium', 'sredniy', 'difficulty', '⭐⭐', '#FFD700', '2026-01-09 08:26:10'),
(18, 'Сложный', 'Hard', 'slozhnyy', 'difficulty', '⭐⭐⭐', '#FF6347', '2026-01-09 08:26:10'),
(19, 'До 30 минут', 'Up to 30 minutes', 'do-30-min', 'duration', '⏱️', '#98FB98', '2026-01-09 08:26:10'),
(20, '30-60 минут', '30-60 minutes', '30-60-min', 'duration', '⏰', '#87CEEB', '2026-01-09 08:26:10'),
(21, '1-2 часа', '1-2 hours', '1-2-hours', 'duration', '🕐', '#FFD700', '2026-01-09 08:26:10'),
(22, '2+ часа', '2+ hours', '2plus-hours', 'duration', '🕒', '#FF6347', '2026-01-09 08:26:10'),
(23, 'Зимние', 'Winter', 'zimnie', 'season', '❄️', '#4682B4', '2026-01-09 08:26:10'),
(24, 'Весенние', 'Spring', 'vesennie', 'season', '🌸', '#FFB6C1', '2026-01-09 08:26:10'),
(25, 'Летние', 'Summer', 'letnie', 'season', '☀️', '#FFD700', '2026-01-09 08:26:10'),
(26, 'Осенние', 'Autumn', 'osennie', 'season', '🍂', '#FF8C00', '2026-01-09 08:26:10'),
(27, 'Круглогодичные', 'Year-round', 'kruglogodichnyie', 'season', '🔄', '#32CD32', '2026-01-09 08:26:10');
-- --------------------------------------------------------
--
-- Структура таблицы `tasks`
--
CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `order` int NOT NULL COMMENT 'Порядок задания в точке',
  `task_text` text NOT NULL COMMENT 'Текст задания',
  `task_text_en` text COMMENT 'Текст задания на английском',
  `task_type` varchar(20) NOT NULL COMMENT 'photo, text, riddle',
  `text_answer` varchar(500) DEFAULT NULL COMMENT 'Правильный ответ',
  `text_answer_hint` varchar(500) DEFAULT NULL COMMENT 'Подсказка к ответу',
  `accept_partial_match` tinyint(1) NOT NULL COMMENT 'Частичное совпадение',
  `max_attempts` int NOT NULL COMMENT 'Максимум попыток',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
--
-- Дамп данных таблицы `tasks`
--
INSERT INTO `tasks` (`id`, `point_id`, `order`, `task_text`, `task_text_en`, `task_type`, `text_answer`, `text_answer_hint`, `accept_partial_match`, `max_attempts`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'Задание №1\nТы у цели! Перед тобой список всех правителей династии Романовых — от Михаила Федоровича до Николая II.\nНо спусти взгляд ниже списка имен. Найди рельефный герб рода Романовых. На нем изображен мифический зверь, который держит меч и щит. У него тело льва, а крылья орла.\n', 'Task No. 1 You are at the finish line! Before you is a list of all the rulers of the Romanov dynasty—from Mikhail Fyodorovich to Nicholas II. But look just below the list of names. Find the embossed coat of arms of the Romanov family. It depicts a mythical beast holding a sword and a shield. It has the body of a lion and the wings of an eagle.', 'text', 'ГРИФОН', NULL, 1, 3, '2026-01-20 22:34:30', '2026-01-20 22:34:30'),
(2, 2, 0, 'Задание №2\n\n👀 Посмотри на гранитную плиту над самим пламенем.\n\n🛡️ На ней лежат отлитые из бронзы символы воинской доблести: боевое знамя, лавровая ветвь и один главный элемент экипировки бойца.\n\n✍️ Напиши, какой предмет лежит на знамени?\n\n--------------------------------------------------------------\n\n💡 (Ответ из одного слова)', 'Task No. 2\n\n👀 Look at the granite slab above the flame itself.\n\n🛡️ On it are symbols of military valor cast in bronze: a battle banner, a laurel branch and one main element of a fighter’s equipment.\n\n✍️ Write what item is on the banner?\n\n--------------------------------------------------------------\n\n💡 (One word answer)', 'text', 'ШЛЕМ|КАСКА', NULL, 1, 3, '2026-01-20 22:58:42', '2026-01-20 22:58:42'),
(5, 3, 0, '📸 Сделай крутое фото: Сфотографируйся на фоне памятника Маршалу Жукову и Исторического музея на память!', '📸 Take a cool photo: Take a photo in front of the monument to Marshal Zhukov and the Historical Museum as a souvenir!', 'photo', NULL, NULL, 1, 3, '2026-01-21 19:14:54', '2026-01-21 19:14:54'),
(6, 4, 0, '📸 ЗАДАНИЕ ДЛЯ КОМАНДЫ\n\n✨ Здесь принято загадывать желания! Встаньте в самый центр бронзового круга, спиной к воротам, и сделайте общее фото.\n\n🪙 По старой традиции, чтобы желание сбылось, нужно бросить монетку через левое плечо так, чтобы она осталась в пределах металлического знака.\n\n🍀 Загадывайте самое смелое желание — говорят, на Нулевом километре они сбываются быстрее!', '📸 TEAM TASK\n\n✨ It’s common to make wishes here! Stand in the very center of the bronze circle, with your back to the gate, and take a group photo.\n\n🪙 According to the old tradition, for a wish to come true, you need to throw a coin over your left shoulder so that it remains within the metal sign.\n\n🍀 Make your wildest wish - they say they come true faster at Kilometer Zero!', 'photo', NULL, NULL, 1, 3, '2026-01-21 19:23:20', '2026-01-21 19:23:20'),
(7, 4, 1, 'Задание №4\n\n🔍 В центре знака находится круг, а вокруг него — квадрат с изображениями животных и растений, ориентированных по сторонам света.\n\n👀 Внимательно посмотри на четыре угла этого бронзового квадрата.\n\n🦉 В одном из них изображена мудрая лесная птица. Напиши название этой птицы.\n\n--------------------------------------------------------------\n\n💡 (Ответ из одного слова)', 'Task No. 4\n\n🔍 In the center of the sign there is a circle, and around it there is a square with images of animals and plants oriented to the cardinal points.\n\n👀 Take a close look at the four corners of this bronze square.\n\n🦉 One of them depicts a wise forest bird. Write the name of this bird.\n\n--------------------------------------------------------------\n\n💡 (One word answer)', 'text', 'СОВА', NULL, 1, 3, '2026-01-21 19:27:51', '2026-01-21 19:27:51'),
(8, 5, 0, '📸 ЗАДАНИЕ: ФОТО-ПАУЗА\n\n🛡️ Эти звери — настоящие стражи времени. С самого XVII века они охраняют вход в главную типографию страны.\n\n🤳 Сделай крупное фото Льва и Единорога на фасаде (или селфи на их фоне).\n\n🔍 Постарайся поймать такой ракурс, чтобы можно было разглядеть детали их схватки!', NULL, 'photo', NULL, NULL, 1, 3, '2026-01-21 19:50:46', '2026-01-21 19:50:46'),
(9, 6, 0, '🔎 Задание №9\n\n🧐 Твоя задача — определить, что это за место. Внимательно посмотри на фасад здания: его современное название огромными буквами написано прямо над входом.\n\n🔠 Чаще всего его сокращают до лаконичной аббревиатуры из трех букв.\n\n--------------------------------------------------------------\n\n✍️ Напиши эту аббревиатуру (3 буквы).', '🔎 Task No. 9\n\n🧐 Your task is to determine what kind of place this is. Take a close look at the façade of the building: its modern name is written in huge letters right above the entrance.\n\n🔠 Most often it is shortened to a laconic abbreviation of three letters.\n\n--------------------------------------------------------------\n\n✍️ Write this abbreviation (3 letters).', 'text', 'цдм', NULL, 1, 3, '2026-01-21 19:59:18', '2026-01-21 19:59:18'),
(10, 7, 0, '🔎 Задание №10\n\n📜 Посмотри на информационную табличку на стене храма. Она хранит в себе историю этого места в цифрах.\n\n📅 На ней указаны два года, связанные с важными этапами строительства и жизни этого здания.\n\n❓ Вопрос: Какова разница в годах между этими двумя датами?\n\n--------------------------------------------------------------\n\n✍️ Ответ — одно число. (Просто вычти из большего года меньший).', '🔎 Task No. 10\n\n📜 Look at the information plaque on the wall of the temple. It contains the history of this place in numbers.\n\n📅 It indicates two years associated with important stages of the construction and life of this building.\n\n❓ Question: What is the difference in years between these two dates?\n\n--------------------------------------------------------------\n\n✍️ The answer is one number. (Simply subtract the smaller year from the larger one).', 'text', '12', NULL, 1, 3, '2026-01-21 20:03:24', '2026-01-21 20:03:24'),
(11, 8, 0, '🔎 Задание №11\n\n🏮 Эта буква «М» интересна не сама по себе. Если ты посмотришь на её основание, то увидишь две цветные горизонтальные полоски.\n\n🧩 Это не просто украшение, а важный шифр для пассажиров, указывающий на линии метро, которые здесь пересекаются.\n\n✍️ Напиши названия этих двух цветов. (Пришли ответ одним сообщением слитно, например: СинийКрасный)', '🔎 Task No. 11\n\n🏮 This letter “M” is not interesting in itself. If you look at its base, you will see two colored horizontal stripes.\n\n🧩 This is not just a decoration, but an important code for passengers, indicating the metro lines that intersect here.\n\n✍️ Write the names of these two colors. (Send the answer in one message, for example: BlueRed)', 'text', 'Оранжевый|Фиолетовый|ОранжевыйФиолетовый', NULL, 1, 4, '2026-01-21 20:07:38', '2026-01-21 20:07:38'),
(12, 9, 0, '📸 ЗАДАНИЕ: ФОТО-ЧЕК\n\n⛓️ Чугунная мощь: Этот памятник выглядит суровым и тяжелым, ведь он полностью отлит из металла.\n\n🛡️ Рассмотри детали: Обойди его вокруг, изучи барельефы с изображениями русских крестьян и солдат — в них застыла история подвига.\n\n🤳 Сделай фото (или селфи) на фоне этой часовни.\n\n✨ Важное условие: Постарайся, чтобы в кадр попал золоченый православный крест на самой вершине шатра!', 'Here\'s your next block:\n📸 TASK: PHOTO CHECK\n\n⛓️ Cast Iron Power: This monument looks harsh and heavy, because it is completely cast from metal.\n\n🛡️ Look at the details: Walk around it, study the bas-reliefs with images of Russian peasants and soldiers - the story of the feat is frozen in them.\n\n🤳 Take a photo (or selfie) in front of this chapel.\n\n✨ Important condition: Try to get the gilded Orthodox cross at the very top of the tent into the frame!', 'photo', NULL, NULL, 1, 3, '2026-01-21 20:10:39', '2026-01-21 20:10:39'),
(13, 10, 0, '🔎 Задание №13\n\n    Там указано, кому он посвящен («Святым равноапостольным...»), и от кого он был установлен.\n\n🇷🇺 Найди слово, которое описывает Россию в этой торжественной фразе.\n\n❓ Вопрос: Какая именно Россия поставила этот памятник?\n\n--------------------------------------------------------------\n\n✍️ Ответ — одно слово (прилагательное).', '🔎 Task No. 13\n\n    It indicates to whom it is dedicated (“To the Saints Equal to the Apostles...”) and from whom it was established.\n\n🇷🇺 Find the word that describes Russia in this solemn phrase.\n\n❓ Question: Which Russia exactly erected this monument?\n\n--------------------------------------------------------------\n\n✍️ The answer is one word (adjective).', 'text', 'БЛАГОДАРНАЯ', NULL, 1, 3, '2026-01-21 20:13:13', '2026-01-21 20:13:13'),
(14, 11, 0, '🔎 ЗАДАНИЕ: УГОЛ ЗРЕНИЯ\n\n🗼 Московская «Пизанская башня»: Посмотри на колокольню храма очень внимательно, сравнивая её вертикальные линии с соседними зданиями. Ты стоишь прямо перед архитектурным феноменом!\n\n📐 Из-за особенностей грунта (тех самых болотистых «куличек») фундамент здания со временем просел, и колокольня приобрела свою знаменитую особенность.\n\n❓ Вопрос: Что не так с колокольней этого храма?\n\n--------------------------------------------------------------\n\n✍️ Опиши её состояние одним глаголом или кратким прилагательным.', '🔎 TASK: VIEW ANGLE\n\n🗼 Moscow “Leaning Tower of Pisa”: Look at the bell tower of the temple very carefully, comparing its vertical lines with neighboring buildings. You are standing right in front of an architectural phenomenon!\n\n📐 Due to the characteristics of the soil (those swampy “wraps”), the foundation of the building sank over time, and the bell tower acquired its famous feature.\n\n❓ Question: What\'s wrong with the bell tower of this temple?\n\n--------------------------------------------------------------\n\n✍️ Describe her condition with one verb or adjective.', 'text', 'наклонена|падает', NULL, 1, 3, '2026-01-21 20:15:25', '2026-01-21 20:15:25'),
(18, 12, 0, '📸 Сделай фото панорамы с видом на Кремль и Москву-реку!', '📸 Take a photo of a panorama with a view of the Kremlin and the Moscow River!', 'photo', NULL, NULL, 1, 3, '2026-01-21 21:14:48', '2026-01-21 21:14:48'),
(19, 13, 0, '📝 Задание:\n\nПеред тобой большая лестница. Посчитай ступени!\n\n❓ Вопрос: Сколько больших ступеней на этой лестнице?\n\n✍️ Отправьте ответ текстом (число)!', '📝 Assignment:\n\nThere is a large staircase in front of you. Count the steps!\n\n❓ Question: How many big steps are there on this staircase?\n\n✍️ Send your answer by text (date)!', 'text', '41', NULL, 1, 10, '2026-01-21 21:16:06', '2026-01-21 21:16:06'),
(20, 14, 0, '📝 Задание:\n\nС моста открывается один из лучших видов на город!\n\n📸 Сделайте общее командное селфи на мосту. В кадре обязательно должны быть:\n• Ваша команда\n• Москва-река прямо под вами\n• Панорама Кремля и собор Василия Блаженного\n\n📷 Отправьте фото в чат!\n', NULL, 'photo', NULL, NULL, 1, 3, '2026-01-21 21:38:39', '2026-01-21 21:38:39'),
(21, 15, 0, '📝 Задание:\n\n📸 Сделай фото на фоне исторического жёлтого здания!\n\n📷 Отправьте фото в чат!', '📝 Assignment:\n\n📸 Take a photo in front of the historical yellow building!\n\n📷 Send a photo to the chat!', 'photo', NULL, NULL, 1, 3, '2026-01-21 21:39:56', '2026-01-21 21:39:56'),
(22, 16, 0, '📝 Задание:\n\nВы это сделали! Перед вами — один из самых узнаваемых храмов мира.\n\nПрямо перед собором стоит первый в Москве скульптурный памятник. Он посвящён Кузьме Минину и князю Дмитрию Пожарскому, которые собрали народное ополчение и освободили город от захватчиков.\n\nРассмотри надпись на гранитном постаменте («Гражданину Минину и князю Пожарскому благодарная Россія...»).\n\n❓ Вопрос: В каком году был установлен этот памятник? На постаменте год указан старым стилем с буквами, но цифры читаются легко.', '📝 Assignment:\n\nYou did it! Before you is one of the most recognizable temples in the world.\n\nRight in front of the cathedral stands the first sculptural monument in Moscow. It is dedicated to Kuzma Minin and Prince Dmitry Pozharsky, who gathered the people\'s militia and liberated the city from the invaders.\n\nLook at the inscription on the granite pedestal (“To Citizen Minin and Prince Pozharsky, grateful Russia...”).\n\n❓ Question: In what year was this monument erected? On the pedestal the year is indicated in the old style with letters, but the numbers are easy to read.', 'text', '1818', NULL, 1, 10, '2026-01-21 21:41:44', '2026-01-21 21:41:44'),
(23, 16, 1, 'Сделайте финальное командное фото на фоне Собора Василия Блаженного!', 'Take your final team photo with St. Basil\'s Cathedral in the background!', 'photo', NULL, NULL, 1, 3, '2026-01-21 21:42:35', '2026-01-21 21:42:35'),
(24, 17, 0, '📝 Задание:\n\nЭтот собор — одна из самых ярких и фотогеничных точек маршрута.\n\n📸 Сделайте классное командное (или селфи) фото на фоне его фасада. Постарайтесь, чтобы в кадр попали и золотые купола, и нарядные белокаменные «кокошники» на крыше!\n\n📷 Отправьте фото в чат!', '📝 Assignment:\n\nThis cathedral is one of the most striking and photogenic points of the route.\n\n📸 Take a cool team (or selfie) photo with its façade in the background. Try to include both the golden domes and the elegant white stone \"kokoshniks\" on the roof!\n\n📷 Send a photo to the chat!', 'photo', NULL, NULL, 1, 3, '2026-01-31 06:00:00', '2026-01-31 06:00:00'),
(25, 18, 0, '📝 Задание:\n\nГУМ — это не просто магазин, а шедевр инженерной мысли XIX века.\n\nВнимательно посмотри на верхнюю часть центрального фасада. Там, среди декоративных элементов, высечены четыре цифры — год постройки этого здания.\n\n❓ Вопрос: Напиши этот год (строительство закончилось в 189...).\n\n✍️ Отправьте ответ текстом (4 цифры)!', '📝 Assignment:\n\nGUM is not just a store, but a masterpiece of 19th century engineering.\n\nLook carefully at the upper part of the central façade. There, among the decorative elements, four digits are carved — the year of construction of this building.\n\n❓ Question: Write this year (construction ended in 189...).\n\n✍️ Send your answer by text (4 digits)!', 'text', '1893', NULL, 1, 3, '2026-01-31 06:00:00', '2026-01-31 06:00:00'),
(26, 19, 0, '📝 Задание:\n\nБратья Третьяковы прорубили этот путь прямо сквозь древнюю крепостную стену Китай-города для удобства покупателей и логистики товаров.\n\n❓ Вопрос: Как официально называется такой тип улицы (сквозной путь)?\n\n💡 Подсказка: Посмотри на синюю табличку с адресом на выходе из арки.\n\n✍️ Отправьте ответ текстом (одно слово)!', '📝 Assignment:\n\nThe Tretyakov brothers cut this path right through the ancient fortress wall of Kitay-Gorod for the convenience of buyers and logistics of goods.\n\n❓ Question: What is the official name of this type of street (through passage)?\n\n💡 Hint: Look at the blue sign with the address at the exit from the arch.\n\n✍️ Send your answer by text (one word)!', 'text', 'ПРОЕЗД', NULL, 1, 3, '2026-01-31 06:00:00', '2026-01-31 06:00:00');
-- --------------------------------------------------------
--
-- Структура таблицы `token_balances`
--
CREATE TABLE `token_balances` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID пользователя',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Текущий баланс токенов',
  `total_deposited` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего пополнено',
  `total_spent` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего потрачено',
  `total_transferred_out` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего переведено другим',
  `total_transferred_in` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего получено от других',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `token_deposits`
--
CREATE TABLE `token_deposits` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL COMMENT 'Сумма в токенах',
  `payment_amount` decimal(15,2) NOT NULL COMMENT 'Сумма в рублях/stars',
  `payment_method` enum('yookassa','telegram_stars') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID платежа',
  `status` enum('pending','completed','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `token_transactions`
--
CREATE TABLE `token_transactions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID пользователя',
  `type` enum('deposit','purchase','transfer_out','transfer_in','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип транзакции',
  `amount` decimal(15,2) NOT NULL COMMENT 'Сумма транзакции',
  `balance_before` decimal(15,2) NOT NULL COMMENT 'Баланс до транзакции',
  `balance_after` decimal(15,2) NOT NULL COMMENT 'Баланс после транзакции',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Описание транзакции',
  `related_user_id` int UNSIGNED DEFAULT NULL COMMENT 'ID связанного пользователя (для переводов)',
  `related_route_id` int UNSIGNED DEFAULT NULL COMMENT 'ID маршрута (для покупок)',
  `payment_method` enum('yookassa','telegram_stars','transfer','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Способ оплаты',
  `external_payment_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID платежа во внешней системе',
  `status` enum('pending','completed','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `users`
--
CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `telegram_id` bigint UNSIGNED NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ru' COMMENT 'Язык интерфейса (ru/en)',
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('USER','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USER',
  `is_banned` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `ban_until` timestamp NULL DEFAULT NULL COMMENT 'Заблокирован до (NULL = не заблокирован)',
  `ban_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Причина блокировки',
  `banned_by` int UNSIGNED DEFAULT NULL COMMENT 'ID админа который заблокировал',
  `banned_at` timestamp NULL DEFAULT NULL COMMENT 'Время блокировки'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `users`
--
INSERT INTO `users` (`id`, `telegram_id`, `username`, `first_name`, `last_name`, `language`, `photo_url`, `role`, `is_banned`, `created_at`, `updated_at`, `last_login`, `ban_until`, `ban_reason`, `banned_by`, `banned_at`) VALUES
(1, 1644233050, 'LEGENDA_SD', '༺Leͥgeͣnͫda༻ᴳᵒᵈ', NULL, 'ru', NULL, 'ADMIN', 0, '2026-01-04 22:41:08', '2026-01-18 16:34:51', NULL, NULL, NULL, NULL, NULL),
(2, 1139810664, 'an1k0nda', 'an1k0nda', NULL, 'ru', NULL, 'ADMIN', 0, '2026-01-06 10:14:32', '2026-01-12 19:47:07', NULL, NULL, NULL, NULL, NULL),
(3, 2000000003, 'Exsydener', 'Exsydener', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(4, 2000000004, 'cdcd3113', 'cdcd3113', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(5, 2000000005, 'Depozit45', 'Depozit45', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(6, 2000000006, 'geshtaltman53', 'geshtaltman53', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(7, 2000000007, 'Ivan5516', 'Ivan5516', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(8, 2000000008, 'az12345658', 'az12345658', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(9, 2000000009, 'slaughter_man', 'slaughter_man', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(10, 2000000010, 'WhyIzik', 'WhyIzik', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(11, 2000000011, 'vikulyababyyy', 'vikulyababyyy', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(12, 2000000012, 'nktevg', 'nktevg', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(13, 2000000013, 'pupa_flex', 'pupa_flex', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(14, 2000000014, 'forsyq', 'forsyq', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(15, 2000000015, 'sidorov_artem94', 'sidorov_artem94', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(16, 2000000016, 'koggda', 'koggda', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(17, 2000000017, 'AGR_42', 'AGR_42', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(18, 2000000018, 'Hugo_Vlad0', 'Hugo_Vlad0', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(19, 2000000019, 'Olgarossia77', 'Olgarossia77', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(20, 2000000020, 'kantiksk', 'kantiksk', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(21, 2000000021, 'ShiZobazis0_0', 'ShiZobazis0_0', NULL, 'ru', NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL),
(22, 7886808180, 'FitoDomik', '🤴', NULL, 'ru', NULL, 'USER', 0, '2026-01-31 05:38:52', '2026-01-31 05:38:52', NULL, NULL, NULL, NULL, NULL);
-- --------------------------------------------------------
--
-- Структура таблицы `user_achievements`
--
CREATE TABLE `user_achievements` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `achievement_id` int UNSIGNED NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `user_achievements`
--
INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(1, 1, 5, '2026-01-18 05:52:33'),
(2, 1, 6, '2026-01-18 05:52:33'),
(3, 1, 10, '2026-01-18 05:52:33'),
(4, 1, 1, '2026-01-18 05:52:33'),
(5, 1, 2, '2026-01-18 05:52:33'),
(6, 1, 3, '2026-01-18 05:52:33'),
(7, 1, 4, '2026-01-18 05:52:33'),
(8, 1, 7, '2026-01-18 05:52:33'),
(9, 1, 8, '2026-01-18 05:52:33'),
(10, 1, 9, '2026-01-18 05:52:33');
-- --------------------------------------------------------
--
-- Структура таблицы `user_audio_settings`
--
CREATE TABLE `user_audio_settings` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `auto_play` tinyint(1) DEFAULT '0' COMMENT 'Автовоспроизведение аудио при переходе к точке',
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ru' COMMENT 'Предпочитаемый язык аудио',
  `voice_id` int DEFAULT '0' COMMENT 'ID голоса (0=мужской, 1=женский)',
  `speech_rate` int DEFAULT '150' COMMENT 'Скорость речи (слов в минуту)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `user_audio_settings`
--
INSERT INTO `user_audio_settings` (`id`, `user_id`, `auto_play`, `language`, `voice_id`, `speech_rate`, `created_at`, `updated_at`) VALUES
(1, 2, 0, 'ru', 0, 150, '2026-01-15 04:05:08', '2026-01-15 04:05:08'),
(2, 1, 1, 'ru', 0, 150, '2026-01-15 11:58:46', '2026-01-31 05:24:22');
-- --------------------------------------------------------
--
-- Структура таблицы `user_hints`
--
CREATE TABLE `user_hints` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `hint_id` int UNSIGNED NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `user_photos`
--
CREATE TABLE `user_photos` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `point_id` int UNSIGNED NOT NULL,
  `file_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telegram file_id',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь: /uploads/users/{id}/file.jpg',
  `file_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA256',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `user_progress`
--
CREATE TABLE `user_progress` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `current_point_id` int UNSIGNED DEFAULT NULL,
  `current_point_order` int UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('IN_PROGRESS','COMPLETED','ABANDONED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN_PROGRESS',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `points_completed` int UNSIGNED NOT NULL DEFAULT '0',
  `photo_hashes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON хешей (антифрод)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Дамп данных таблицы `user_progress`
--
INSERT INTO `user_progress` (`id`, `user_id`, `route_id`, `current_point_id`, `current_point_order`, `status`, `started_at`, `completed_at`, `points_completed`, `photo_hashes`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 19, 19, 'COMPLETED', '2026-01-22 07:00:00', '2026-01-22 09:00:00', 19, NULL, '2026-01-22 07:00:00', '2026-01-22 09:00:00'),
(2, 3, 1, 19, 19, 'COMPLETED', '2026-01-22 08:00:00', '2026-01-22 10:00:00', 19, NULL, '2026-01-22 08:00:00', '2026-01-22 10:00:00'),
(3, 4, 1, 19, 19, 'COMPLETED', '2026-01-22 11:00:00', '2026-01-22 13:00:00', 19, NULL, '2026-01-22 11:00:00', '2026-01-22 13:00:00'),
(4, 5, 1, 19, 19, 'COMPLETED', '2026-01-23 07:00:00', '2026-01-23 09:00:00', 19, NULL, '2026-01-23 07:00:00', '2026-01-23 09:00:00'),
(5, 6, 1, 19, 19, 'COMPLETED', '2026-01-23 08:00:00', '2026-01-23 10:00:00', 19, NULL, '2026-01-23 08:00:00', '2026-01-23 10:00:00'),
(6, 7, 1, 19, 19, 'COMPLETED', '2026-01-23 11:00:00', '2026-01-23 13:00:00', 19, NULL, '2026-01-23 11:00:00', '2026-01-23 13:00:00'),
(7, 8, 1, 19, 19, 'COMPLETED', '2026-01-24 07:00:00', '2026-01-24 09:00:00', 19, NULL, '2026-01-24 07:00:00', '2026-01-24 09:00:00'),
(8, 9, 1, 19, 19, 'COMPLETED', '2026-01-24 08:00:00', '2026-01-24 10:00:00', 19, NULL, '2026-01-24 08:00:00', '2026-01-24 10:00:00'),
(9, 10, 1, 19, 19, 'COMPLETED', '2026-01-24 11:00:00', '2026-01-24 13:00:00', 19, NULL, '2026-01-24 11:00:00', '2026-01-24 13:00:00'),
(10, 11, 1, 19, 19, 'COMPLETED', '2026-01-25 07:00:00', '2026-01-25 09:00:00', 19, NULL, '2026-01-25 07:00:00', '2026-01-25 09:00:00'),
(11, 12, 1, 19, 19, 'COMPLETED', '2026-01-25 08:00:00', '2026-01-25 10:00:00', 19, NULL, '2026-01-25 08:00:00', '2026-01-25 10:00:00'),
(12, 13, 1, 19, 19, 'COMPLETED', '2026-01-25 11:00:00', '2026-01-25 13:00:00', 19, NULL, '2026-01-25 11:00:00', '2026-01-25 13:00:00'),
(13, 14, 1, 19, 19, 'COMPLETED', '2026-01-26 07:00:00', '2026-01-26 09:00:00', 19, NULL, '2026-01-26 07:00:00', '2026-01-26 09:00:00'),
(14, 15, 1, 19, 19, 'COMPLETED', '2026-01-26 08:00:00', '2026-01-26 10:00:00', 19, NULL, '2026-01-26 08:00:00', '2026-01-26 10:00:00'),
(15, 16, 1, 19, 19, 'COMPLETED', '2026-01-26 11:00:00', '2026-01-26 13:00:00', 19, NULL, '2026-01-26 11:00:00', '2026-01-26 13:00:00'),
(16, 17, 1, 19, 19, 'COMPLETED', '2026-01-19 07:00:00', '2026-01-19 09:00:00', 19, NULL, '2026-01-19 07:00:00', '2026-01-19 09:00:00'),
(17, 18, 1, 19, 19, 'COMPLETED', '2026-01-19 08:00:00', '2026-01-19 10:00:00', 19, NULL, '2026-01-19 08:00:00', '2026-01-19 10:00:00'),
(18, 19, 1, 19, 19, 'COMPLETED', '2026-01-19 11:00:00', '2026-01-19 13:00:00', 19, NULL, '2026-01-19 11:00:00', '2026-01-19 13:00:00'),
(19, 20, 1, 19, 19, 'COMPLETED', '2026-01-28 07:00:00', '2026-01-28 09:00:00', 19, NULL, '2026-01-28 07:00:00', '2026-01-28 09:00:00'),
(20, 21, 1, 19, 19, 'COMPLETED', '2026-01-28 08:00:00', '2026-01-28 10:00:00', 19, NULL, '2026-01-28 08:00:00', '2026-01-28 10:00:00');
-- --------------------------------------------------------
--
-- Структура таблицы `user_search_limits`
--
CREATE TABLE `user_search_limits` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID пользователя',
  `search_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Количество поисков',
  `first_search_at` timestamp NULL DEFAULT NULL COMMENT 'Время первого поиска в окне',
  `blocked_until` timestamp NULL DEFAULT NULL COMMENT 'Заблокирован до',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------
--
-- Структура таблицы `user_sessions`
--
CREATE TABLE `user_sessions` (
  `id` int UNSIGNED NOT NULL,
  `telegram_id` bigint UNSIGNED NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Одноразовый токен',
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL COMMENT 'Срок 5 минут',
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Индексы сохранённых таблиц
--
--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`);
--
-- Индексы таблицы `audio_cache`
--
ALTER TABLE `audio_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cache` (`point_id`,`language`,`text_hash`),
  ADD KEY `idx_point_id` (`point_id`),
  ADD KEY `idx_language` (`language`),
  ADD KEY `idx_text_hash` (`text_hash`),
  ADD KEY `idx_expires_at` (`expires_at`);
--
-- Индексы таблицы `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);
--
-- Индексы таблицы `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_progress_id` (`progress_id`),
  ADD KEY `certificates_ibfk_2` (`route_id`);
--
-- Индексы таблицы `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_is_active` (`is_active`);
--
-- Индексы таблицы `hints`
--
ALTER TABLE `hints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_point_id` (`point_id`),
  ADD KEY `idx_level` (`level`);
--
-- Индексы таблицы `moderation_tasks`
--
ALTER TABLE `moderation_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned` (`assigned_to`);
--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);
--
-- Индексы таблицы `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_route_id` (`route_id`),
  ADD KEY `idx_points_audio_enabled` (`audio_enabled`),
  ADD KEY `idx_points_audio_language` (`audio_language`);
--
-- Индексы таблицы `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `valid_until` (`valid_until`);
--
-- Индексы таблицы `promo_code_uses`
--
ALTER TABLE `promo_code_uses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promo_code_id` (`promo_code_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `route_id` (`route_id`);
--
-- Индексы таблицы `reference_images`
--
ALTER TABLE `reference_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_point_id` (`point_id`);
--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_route_progress` (`user_id`,`route_id`,`progress_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reviews_route` (`route_id`,`created_at`),
  ADD KEY `idx_reviews_user` (`user_id`,`created_at`),
  ADD KEY `reviews_ibfk_3` (`progress_id`);
--
-- Индексы таблицы `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_city_id` (`city_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_routes_difficulty` (`difficulty`),
  ADD KEY `idx_routes_duration` (`duration_minutes`),
  ADD KEY `idx_routes_season` (`season`);
--
-- Индексы таблицы `route_tags`
--
ALTER TABLE `route_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_route_tag` (`route_id`,`tag_id`),
  ADD KEY `idx_route_id` (`route_id`),
  ADD KEY `idx_tag_id` (`tag_id`);
--
-- Индексы таблицы `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);
--
-- Индексы таблицы `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD KEY `idx_type` (`type`);
--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `point_id` (`point_id`);
--
-- Индексы таблицы `token_balances`
--
ALTER TABLE `token_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
--
-- Индексы таблицы `token_deposits`
--
ALTER TABLE `token_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_id` (`payment_id`);
--
-- Индексы таблицы `token_transactions`
--
ALTER TABLE `token_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_related_user` (`related_user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_token_transactions_route` (`related_route_id`);
--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `telegram_id` (`telegram_id`),
  ADD KEY `idx_telegram_id` (`telegram_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_users_banned` (`is_banned`),
  ADD KEY `idx_users_ban_until` (`ban_until`),
  ADD KEY `idx_users_banned_by` (`banned_by`);
--
-- Индексы таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`),
  ADD KEY `idx_user_id` (`user_id`);
--
-- Индексы таблицы `user_audio_settings`
--
ALTER TABLE `user_audio_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);
--
-- Индексы таблицы `user_hints`
--
ALTER TABLE `user_hints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_route` (`user_id`,`route_id`),
  ADD KEY `idx_hint_id` (`hint_id`),
  ADD KEY `idx_point_id` (`point_id`),
  ADD KEY `user_hints_ibfk_2` (`route_id`);
--
-- Индексы таблицы `user_photos`
--
ALTER TABLE `user_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_point_id` (`point_id`);
--
-- Индексы таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `current_point_id` (`current_point_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_route_id` (`route_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_progress_completed` (`route_id`,`status`,`completed_at`);
--
-- Индексы таблицы `user_search_limits`
--
ALTER TABLE `user_search_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
--
-- Индексы таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_telegram_id` (`telegram_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);
--
-- AUTO_INCREMENT для сохранённых таблиц
--
--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT для таблицы `audio_cache`
--
ALTER TABLE `audio_cache`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `hints`
--
ALTER TABLE `hints`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;
--
-- AUTO_INCREMENT для таблицы `moderation_tasks`
--
ALTER TABLE `moderation_tasks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `points`
--
ALTER TABLE `points`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT для таблицы `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `promo_code_uses`
--
ALTER TABLE `promo_code_uses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `reference_images`
--
ALTER TABLE `reference_images`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT для таблицы `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `route_tags`
--
ALTER TABLE `route_tags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT для таблицы `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT для таблицы `token_balances`
--
ALTER TABLE `token_balances`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `token_deposits`
--
ALTER TABLE `token_deposits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `token_transactions`
--
ALTER TABLE `token_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT для таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT для таблицы `user_audio_settings`
--
ALTER TABLE `user_audio_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT для таблицы `user_hints`
--
ALTER TABLE `user_hints`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `user_photos`
--
ALTER TABLE `user_photos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT для таблицы `user_search_limits`
--
ALTER TABLE `user_search_limits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Ограничения внешнего ключа сохраненных таблиц
--
--
-- Ограничения внешнего ключа таблицы `audio_cache`
--
ALTER TABLE `audio_cache`
  ADD CONSTRAINT `fk_audio_cache_point_id` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_3` FOREIGN KEY (`progress_id`) REFERENCES `user_progress` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `hints`
--
ALTER TABLE `hints`
  ADD CONSTRAINT `hints_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `points`
--
ALTER TABLE `points`
  ADD CONSTRAINT `points_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD CONSTRAINT `promo_codes_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL;
--
-- Ограничения внешнего ключа таблицы `promo_code_uses`
--
ALTER TABLE `promo_code_uses`
  ADD CONSTRAINT `promo_code_uses_ibfk_1` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promo_code_uses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promo_code_uses_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL;
--
-- Ограничения внешнего ключа таблицы `reference_images`
--
ALTER TABLE `reference_images`
  ADD CONSTRAINT `reference_images_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`progress_id`) REFERENCES `user_progress` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `route_tags`
--
ALTER TABLE `route_tags`
  ADD CONSTRAINT `fk_route_tags_route_id` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_route_tags_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `token_balances`
--
ALTER TABLE `token_balances`
  ADD CONSTRAINT `fk_token_balances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `token_deposits`
--
ALTER TABLE `token_deposits`
  ADD CONSTRAINT `fk_token_deposits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `token_transactions`
--
ALTER TABLE `token_transactions`
  ADD CONSTRAINT `fk_token_transactions_related_user` FOREIGN KEY (`related_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_token_transactions_route` FOREIGN KEY (`related_route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_token_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `user_audio_settings`
--
ALTER TABLE `user_audio_settings`
  ADD CONSTRAINT `fk_user_audio_settings_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `user_hints`
--
ALTER TABLE `user_hints`
  ADD CONSTRAINT `user_hints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_hints_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_hints_ibfk_3` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_hints_ibfk_4` FOREIGN KEY (`hint_id`) REFERENCES `hints` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `user_photos`
--
ALTER TABLE `user_photos`
  ADD CONSTRAINT `user_photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_photos_ibfk_2` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;
--
-- Ограничения внешнего ключа таблицы `user_search_limits`
--
ALTER TABLE `user_search_limits`
  ADD CONSTRAINT `fk_user_search_limits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
