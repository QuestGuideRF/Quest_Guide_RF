-- phpMyAdmin SQL Dump
-- version 5.2.1-1.el8
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Фев 14 2026 г., 04:30
-- Версия сервера: 8.0.25-15
-- Версия PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `u3403708_QuestGuideFR`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание на английском',
  `icon` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '?',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Общие',
  `category_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Категория на английском',
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

INSERT INTO `achievements` (`id`, `name`, `name_en`, `description`, `description_en`, `icon`, `category`, `category_en`, `order`, `is_hidden`, `condition_type`, `condition_value`, `created_at`, `updated_at`) VALUES
(1, 'Первые шаги', 'First Steps', 'Завершите свой первый маршрут', 'Complete your first route', '🎯', 'Прогресс', 'Progress', 1, 0, 'routes_completed', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(2, 'Исследователь', 'Explorer', 'Пройдите 5 маршрутов', 'Complete 5 routes', '🗺️', 'Прогресс', 'Progress', 2, 0, 'routes_completed', 5, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(3, 'Мастер квестов', 'Quest Master', 'Пройдите 10 маршрутов', 'Complete 10 routes', '🏆', 'Прогресс', 'Progress', 3, 0, 'routes_completed', 10, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(4, 'Коллекционер точек', 'Point Collector', 'Посетите 50 точек', 'Visit 50 points', '📍', 'Прогресс', 'Progress', 4, 0, 'points_completed', 50, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(5, 'Фотограф', 'Photographer', 'Сделайте 100 фотографий', 'Take 100 photos', '📸', 'Активность', 'Activity', 5, 0, 'photos_taken', 100, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(6, 'Перфекционист', 'Perfectionist', 'Пройдите маршрут на 100%', 'Complete a route 100%', '💯', 'Качество', 'Quality', 6, 0, 'perfect_route', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(7, 'Быстрый', 'Speedster', 'Завершите маршрут быстрее времени', 'Complete a route faster than time', '⚡', 'Челленджи', 'Challenges', 7, 0, 'fast_completion', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(8, 'Ночной странник', 'Night Walker', 'Пройдите квест ночью (22:00-06:00)', 'Complete a quest at night (22:00-06:00)', '🌙', 'Челленджи', 'Challenges', 8, 1, 'night_quest', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(9, 'Ранняя пташка', 'Early Bird', 'Начните квест до 8 утра', 'Start a quest before 8 AM', '🌅', 'Челленджи', 'Challenges', 9, 1, 'early_bird', 1, '2026-01-04 05:51:28', '2026-01-04 05:51:28'),
(10, 'Легенда', 'Legend', 'Получите все достижения', 'Earn all achievements', '👑', 'Особые', 'Special', 10, 1, 'all_achievements', 13, '2026-01-04 05:51:28', '2026-02-05 20:20:17'),
(11, 'Начало пути', 'Getting Started', 'Пригласите 3 друзей, которые купят квест', 'Invite 3 friends who buy a quest', '🌱', 'Партнёрка', 'Referral', 11, 0, 'referrals_paid', 3, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(12, 'Активный участник', 'Active Participant', 'Пригласите 10 друзей, которые купят квест', 'Invite 10 friends who buy a quest', '🔥', 'Партнёрка', 'Referral', 12, 0, 'referrals_paid', 10, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(13, 'Главный фанат', 'Super Fan', 'Пригласите 30 друзей, которые купят квест', 'Invite 30 friends who buy a quest', '🏆', 'Партнёрка', 'Referral', 13, 0, 'referrals_paid', 30, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(14, 'Официальный партнёр', 'Official Partner', 'Пригласите 100 друзей, которые купят квест', 'Invite 100 friends who buy a quest', '👑', 'Партнёрка', 'Referral', 14, 0, 'referrals_paid', 100, '2026-02-05 20:20:17', '2026-02-05 20:20:17');

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
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creator_id` int UNSIGNED DEFAULT NULL COMMENT 'ID модератора-создателя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cities`
--

INSERT INTO `cities` (`id`, `name`, `name_en`, `description`, `description_en`, `is_active`, `created_at`, `updated_at`, `creator_id`) VALUES
(1, 'Москва', 'Moscow', 'Москва 🔥 Главный мегаполис страны, который никогда не берет паузу. Это город бесконечных возможностей, где история пишется в режиме реального времени. Здесь амбиции превращаются в рекорды, а старина встречается с будущим на каждом перекрестке. Если хочешь почувствовать пульс страны — он здесь.', 'Moscow 🔥 The main metropolis of the country, which never takes a break. This is a city of endless possibilities where history is written in real time. Here ambitions turn into records, and the past meets the future at every crossroads. If you want to feel the pulse of the country, it is here.', 1, '2026-01-20 19:28:04', '2026-01-20 19:30:34', NULL);

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
-- Структура таблицы `moderator_balances`
--

CREATE TABLE `moderator_balances` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Текущий баланс',
  `total_earned` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего заработано',
  `total_withdrawn` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего выведено',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Балансы модераторов';

-- --------------------------------------------------------

--
-- Структура таблицы `moderator_requests`
--

CREATE TABLE `moderator_requests` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Сообщение-обоснование',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий админа',
  `reviewed_by` int UNSIGNED DEFAULT NULL COMMENT 'ID админа, рассмотревшего заявку',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заявки на получение прав модератора';

-- --------------------------------------------------------

--
-- Структура таблицы `moderator_transactions`
--

CREATE TABLE `moderator_transactions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID модератора',
  `type` enum('earning','withdrawal','adjustment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип транзакции',
  `amount` decimal(15,2) NOT NULL COMMENT 'Сумма',
  `route_id` int UNSIGNED DEFAULT NULL COMMENT 'ID маршрута (для earning)',
  `buyer_user_id` int UNSIGNED DEFAULT NULL COMMENT 'ID покупателя (для earning)',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Транзакции модераторов';

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
-- Структура таблицы `platform_earnings`
--

CREATE TABLE `platform_earnings` (
  `id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `buyer_user_id` int UNSIGNED NOT NULL COMMENT 'Кто купил',
  `moderator_id` int UNSIGNED NOT NULL COMMENT 'Модератор-создатель',
  `total_amount` decimal(15,2) NOT NULL COMMENT 'Полная стоимость маршрута',
  `commission_percent` decimal(5,2) NOT NULL COMMENT 'Процент комиссии',
  `platform_amount` decimal(15,2) NOT NULL COMMENT 'Сумма комиссии платформы',
  `moderator_amount` decimal(15,2) NOT NULL COMMENT 'Сумма модератору',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='История доходов платформы';

-- --------------------------------------------------------

--
-- Структура таблицы `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` int UNSIGNED NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Настройки платформы';

--
-- Дамп данных таблицы `platform_settings`
--

INSERT INTO `platform_settings` (`id`, `key`, `value`, `description`, `updated_at`) VALUES
(1, 'commission_percent', '10', 'Процент комиссии платформы (от 3 до 30)', '2026-02-01 13:02:35'),
(2, 'commission_min', '3', 'Минимальный процент комиссии', '2026-02-01 13:02:35'),
(3, 'commission_max', '30', 'Максимальный процент комиссии', '2026-02-01 13:02:35'),
(4, 'moderator_enabled', '1', 'Включена ли система модераторов', '2026-02-01 13:02:35'),
(5, 'review_reward_amount', '10', 'Бонус грошей за оставленный отзыв', '2026-02-05 20:20:17'),
(6, 'review_reward_enabled', '1', 'Включены ли бонусы за отзывы', '2026-02-05 20:20:17'),
(7, 'referral_level1_tokens', '20', 'Гроши за реферала на уровне 1', '2026-02-05 20:20:17'),
(8, 'referral_level2_discount', '15', 'Процент скидки на уровне 2', '2026-02-05 20:20:17'),
(9, 'referral_level1_required', '3', 'Рефералов для уровня 1', '2026-02-05 20:20:17'),
(10, 'referral_level2_required', '10', 'Рефералов для уровня 2', '2026-02-05 20:20:17'),
(11, 'referral_level3_required', '30', 'Рефералов для уровня 3', '2026-02-05 20:20:17'),
(12, 'referral_level4_required', '100', 'Рефералов для уровня 4', '2026-02-05 20:20:17'),
(13, 'survey_reward_amount', '5', 'Награда за прохождение опроса (гроши)', '2026-02-08 18:23:05'),
(14, 'survey_reward_enabled', '1', 'Включена ли награда за опрос', '2026-02-08 18:23:05'),
(15, 'quiz_reward_per_correct', '2', 'Награда за каждый правильный ответ в квизе (гроши)', '2026-02-08 18:23:05');

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

INSERT INTO `points` (`id`, `route_id`, `order`, `name`, `name_en`, `address`, `fact_text`, `fact_text_en`, `min_people`, `latitude`, `longitude`, `is_free`, `created_at`, `updated_at`, `audio_enabled`, `audio_file_path`, `audio_language`, `audio_text`, `audio_text_en`, `audio_file_path_ru`, `audio_file_path_en`, `task_type`, `text_answer`, `text_answer_hint`, `accept_partial_match`, `max_attempts`) VALUES
(1, 1, 1, '📍 Точка №1 Александровский Сад', '📍 Point No. 1 Alexander Garden', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nЭтот обелиск — настоящий «хамелеон» истории. Его установили в 1914 году в честь 300-летия дома Романовых: на гранях камня были выбиты имена всех царствовавших представителей династии — от Михаила Фёдоровича до Николая II. Рядом с именами красовался родовой герб Романовых с изображением мифического крылатого зверя-стража 🛡️. После Октябрьской революции памятник не снесли, а переделали: имена царей тщательно стёсали, а на их месте выбили имена революционных мыслителей — Маркса, Энгельса, Плеханова и других. Обелиск переименовали в «Памятник выдающимся мыслителям и деятелям борьбы за освобождение трудящихся». Лишь в 2013 году, к 400-летию дома Романовых, памятнику вернули первоначальный облик по сохранившимся чертежам и фотографиям. Если присмотреться к поверхности камня, до сих пор заметны следы от стачивания — камень чуть неровный там, где когда-то были старые надписи. Так один монумент рассказывает сразу три эпохи: царскую Россию, советскую власть и современное восстановление памяти.\r\n', 'HISTORICAL FACT\r\n\r\nThis obelisk is a real “chameleon” of history. It was installed in 1914 in honor of the 300th anniversary of the Romanov dynasty: the names of all the reigning representatives of the dynasty - from Mikhail Fedorovich to Nicholas II - were engraved on the edges of the stone. Next to the names was the Romanov family coat of arms with the image of a mythical winged guardian beast 🛡️. After the October Revolution, the monument was not demolished, but remade: the names of the tsars were carefully erased, and in their place the names of revolutionary thinkers - Marx, Engels, Plekhanov and others - were knocked out. The obelisk was renamed “Monument to Outstanding Thinkers and Activists in the Struggle for the Liberation of the Working People.” Only in 2013, on the occasion of the 400th anniversary of the House of Romanov, the monument was restored to its original appearance based on surviving drawings and photographs. If you look closely at the surface of the stone, traces of grinding are still visible - the stone is slightly uneven where the old inscriptions once were. So one monument tells three eras at once: Tsarist Russia, Soviet power and modern restoration of memory.', 1, 55.75370000, 37.61485300, 0, '2026-01-20 16:34:15', '2026-02-14 01:21:29', 1, NULL, 'ru', '🚇 Как добраться:\r\n1. Ближайшие станции метро: «Александровский сад», «Библиотека им. Ленина» или «Охотный Ряд».\r\n2. Выходи к Историческому музею и Манежной площади.\r\n3. Найди главные чугунные ворота — вход в Александровский сад.\r\n\r\n👣 Куда идти:\r\n1. Войди в сад через главные ворота и иди по аллее прямо.\r\n2. Вечный огонь и Кремлёвская стена будут слева от тебя.\r\n3. Пройди мимо Поста №1 ещё примерно 50–70 метров вглубь сада.\r\n4. Справа от дорожки ищи серый каменный обелиск с золотым орлом.\r\n\r\n🎯 Твоя цель:\r\nРОМАНОВСКИЙ ОБЕЛИСК\r\n(Подойди к нему вплотную)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '🚇 How to get there:\r\n1. Nearest metro stations: “Alexandrovsky Sad”, “Biblioteka im. Lenin\" or \"Okhotny Ryad\".\r\n2. Go to the Historical Museum and Manezhnaya Square.\r\n3. Find the main cast-iron gate - the entrance to the Alexander Garden.\r\n\r\n👣Where to go:\r\n1. Enter the garden through the main gate and walk straight along the alley.\r\n2. The Eternal Flame and the Kremlin Wall will be on your left.\r\n3. Walk past Post No. 1 about another 50–70 meters deeper into the garden.\r\n4. To the right of the path, look for a gray stone obelisk with a golden eagle.\r\n\r\n🎯 Your goal:\r\nROMANOVSKY OBELISK\r\n(Get close to him)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', '', '', 'text', '', '', 1, 3),
(2, 1, 2, '📍 Точка №2 Вечный огонь (Могила Неизвестного Солдата)', '📍 Point No. 2 Eternal Flame (Tomb of the Unknown Soldier)', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nВечный огонь у Кремлёвской стены горит непрерывно с 8 мая 1967 года. Его зажгли от пламени с Марсова поля в Ленинграде — там с 1917 года горел первый в России Вечный огонь в память о жертвах революции. Факел доставили в Москву на бронетранспортёре по Ленинградскому шоссе; на всём пути его встречали жители городов и сёл. Идея создать мемориал в Москве принадлежала первому секретарю ЦК КПСС Леониду Брежневу: прах неизвестного солдата перенесли из братской могилы у 41-го километра Ленинградского шоссе, где в 1941 году шли тяжёлые бои. На гранитной плите высечены слова: «Имя твоё неизвестно, подвиг твой бессмертен». Каждые три часа здесь проходит торжественная смена караула — Пост №1. Если повезёт, ты увидишь знаменитый «печатный шаг»: караульные поднимают ногу почти параллельно земле и ставят её с характерным ударом. Такой шаг придумали ещё в царской армии для парадов.\r\n', 'HISTORICAL FACT\r\n\r\nThe eternal flame at the Kremlin wall has been burning continuously since May 8, 1967. It was lit from a flame from the Champ de Mars in Leningrad - the first Eternal Flame in Russia burned there since 1917 in memory of the victims of the revolution. The torch was delivered to Moscow on an armored personnel carrier along the Leningradskoye Highway; All along the way he was met by residents of cities and villages. The idea to create a memorial in Moscow belonged to the first secretary of the CPSU Central Committee, Leonid Brezhnev: the ashes of an unknown soldier were transferred from a mass grave at the 41st kilometer of the Leningradskoye Highway, where heavy fighting took place in 1941. The words are carved on the granite slab: “Your name is unknown, your feat is immortal.” Every three hours there is a ceremonial changing of the guard - Post No. 1. If you are lucky, you will see the famous “sign step”: the guards raise their leg almost parallel to the ground and place it with a characteristic blow. This step was invented in the tsarist army for parades.', 1, 55.75477500, 37.61609900, 0, '2026-01-20 16:54:27', '2026-02-14 01:22:08', 1, NULL, 'ru', '👣 Куда идти:\r\n1. 🚶 Встань спиной к Романовскому обелиску и возвращайся по аллее к выходу из сада.\r\n2. 🏰 Кремлёвская стена теперь будет по правую руку.\r\n3. 🔥 Примерно через 50 метров ты увидишь Почётный караул и неугасающее пламя у подножия стены.\r\n4. 🎖️ Перед тобой главный военный мемориал страны.\r\n\r\n🎯 Твоя цель:\r\nМОГИЛА НЕИЗВЕСТНОГО СОЛДАТА\r\n(Подойди к центральной части мемориала, где горит огонь)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. 🚶 Stand with your back to the Romanovsky Obelisk and return along the alley to the exit from the garden.\r\n2. 🏰 The Kremlin wall will now be on the right hand.\r\n3. 🔥 After about 50 meters you will see the Honor Guard and the unquenchable flame at the foot of the wall.\r\n4. 🎖️ In front of you is the main war memorial of the country.\r\n\r\n🎯 Your goal:\r\nTOMB OF THE UNKNOWN SOLDIER\r\n(Go to the central part of the memorial, where the fire is burning)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(3, 1, 3, '📍 Точка №3 Памятник великому полководцу', '📍 Point No. 3 Monument to the great commander', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nПеред тобой памятник маршалу Георгию Жукову — единственному полководцу, четырежды удостоенному звания Героя Советского Союза. Существует легенда: Сталин сам хотел принимать Парад Победы 24 июня 1945 года верхом на коне, но на репетиции жеребец по кличке «Кумир» сбросил вождя. Тогда принимать парад поручили Жукову — на том же «Кумире». Коня для маршала искали по всей стране: нужен был идеально белый, высокий и выдержанный жеребец. Нашли в кавалерийском полку. Памятник установили только в 1995 году, к 50-летию Победы; долгое время на Манежной площади не было монумента полководцу. Скульптор Вячеслав Клыков изобразил Жукова на том самом параде — в парадном мундире, на вздыбленном коне, попирающем копытами штандарты нацистской Германии. Памятник стоит не совсем там, где изначально планировали: его сдвинули с оси Красной площади, чтобы не закрывать вид на Исторический музей.\r\n', 'HISTORICAL FACT\r\n\r\nIn front of you is a monument to Marshal Georgy Zhukov - the only commander who was awarded the title of Hero of the Soviet Union four times. There is a legend: Stalin himself wanted to take part in the Victory Parade on June 24, 1945 on horseback, but during the rehearsal the stallion nicknamed “Idol” threw the leader off. Then Zhukov was assigned to host the parade - on the same “Idol”. They were looking for a horse for the marshal all over the country: they needed a perfectly white, tall and seasoned stallion. Found in a cavalry regiment. The monument was erected only in 1995, on the 50th anniversary of the Victory; For a long time there was no monument to the commander on Manezhnaya Square. Sculptor Vyacheslav Klykov depicted Zhukov at that very parade - in a ceremonial uniform, on a rearing horse, trampling the standards of Nazi Germany with its hooves. The monument does not stand exactly where it was originally planned: it was moved from the axis of Red Square so as not to block the view of the Historical Museum.', 1, 55.75579600, 37.61690800, 0, '2026-01-21 13:14:49', '2026-02-14 01:22:47', 1, NULL, 'ru', '👣 Куда идти:\r\n1. 🚶 Продолжаем путь! От Вечного огня иди к выходу из Александровского сада — к тем самым чугунным воротам, через которые ты входил.\r\n2. 🏰 Кремлёвская стена и Вечный огонь должны оставаться справа от тебя.\r\n3. 🚩 Выйди через ворота на Манежную площадь — это большая открытая площадь перед Красной площадью и Историческим музеем. Прямо перед тобой — большое красное здание Исторического музея, расположенное между Манежной и Красной площадями.\r\n4. 🏇 Перед его главным фасадом ты увидишь величественный памятник всаднику.\r\n\r\n🎯 Твоя цель:\r\nПАМЯТНИК ПОЛКОВОДЦУ.\r\n(Подойди к подножию памятника)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. 🚶 Let\'s continue our journey! From the Eternal Flame, go to the exit from the Alexander Garden - to the same cast-iron gate through which you entered.\r\n2. 🏰 The Kremlin wall and the Eternal Flame should remain to your right.\r\n3. 🚩 Exit through the gate to Manezhnaya Square - this is a large open square in front of Red Square and the Historical Museum. Right in front of you is the large red building of the Historical Museum, located between Manezhnaya and Red Squares.\r\n4. 🏇 In front of its main facade you will see a majestic monument to the horseman.\r\n\r\n🎯 Your goal:\r\nMONUMENT TO THE COMMANDER.\r\n(Go to the foot of the monument)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(4, 1, 4, '📍 Точка №4 Нулевой километр автодорог России', '📍 Point No. 4 Zero kilometer of Russian roads', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nБронзовый знак «Нулевой километр автодорог Российской Федерации» заложили в 1995 году — от него официально отсчитывают километраж федеральных трасс. Но географически «нулевая точка» для измерения дорог в Москве раньше находилась у здания Центрального телеграфа на Тверской — там до революции стоял верстовой столб. Нынешний знак создал скульптор Александр Рукавишников: в центре — круг с восьмилучевой розой ветров, вокруг — квадрат с изображениями животных и птиц по сторонам света (в каждом углу квадрата — своё символическое существо, олицетворяющее силы природы 🦅). Сюда приезжают туристы со всего мира: по традиции нужно встать в центр круга спиной к воротам и бросить монетку через левое плечо — тогда желание сбудется. Монеты регулярно собирают и передают на благотворительность. Воскресенские ворота, между которыми лежит знак, когда-то были частью Китайгородской стены; через них въезжали цари, возвращаясь в Кремль.\r\n', 'HISTORICAL FACT\r\n\r\nThe bronze sign “Zero Kilometer of Highways of the Russian Federation” was laid in 1995 - the mileage of federal highways is officially calculated from it. But geographically, the “zero point” for measuring roads in Moscow used to be located at the Central Telegraph building on Tverskaya - there was a milestone there before the revolution. The current sign was created by the sculptor Alexander Rukavishnikov: in the center there is a circle with an eight-pointed wind rose, around there is a square with images of animals and birds on the cardinal points (each corner of the square has its own symbolic creature, personifying the forces of nature 🦅). Tourists from all over the world come here: according to tradition, you need to stand in the center of the circle with your back to the gate and throw a coin over your left shoulder - then your wish will come true. Coins are regularly collected and donated to charity. The Resurrection Gate, between which the sign lies, was once part of the Kitai-Gorod wall; Tsars entered through them, returning to the Kremlin.', 1, 55.75564800, 37.61796400, 0, '2026-01-21 13:23:17', '2026-02-14 01:23:16', 1, NULL, 'ru', '👣 Куда идти:\r\n🚶 Встань лицом к памятнику полководцу, которого ты только что отгадал, и посмотри налево.\r\n🏰 Ты увидишь красные ворота с двумя остроконечными шпилями — это Воскресенские ворота, вход на Красную площадь.\r\n📍 Подойди к ним. Прямо в проезде, перед воротами, ты заметишь вмонтированный в брусчатку блестящий бронзовый знак.\r\n✨ Это особое место — отсюда начинается отсчёт всех дорог страны.\r\n\r\n🎯 Твоя цель:\r\nНУЛЕВОЙ КИЛОМЕТР\r\n(Встань в самый центр знака)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🚶 Stand facing the monument to the commander you just guessed and look to the left.\r\n🏰 You will see a red gate with two pointed spiers - this is the Resurrection Gate, the entrance to Red Square.\r\n📍 Come to them. Right in the driveway, in front of the gate, you will notice a shiny bronze sign embedded in the paving stones.\r\n✨ This is a special place - the countdown of all roads in the country begins from here.\r\n\r\n🎯 Your goal:\r\nZERO KILOMETER\r\n(Stand in the very center of the sign)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'text', '', '', 1, 3),
(5, 1, 5, '📍 Точка №5 Казанский собор', '📍 Point No. 5 Kazan Cathedral', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nКазанский собор на Красной площади — настоящий «феникс»: его дважды строили и один раз полностью уничтожили. Первый храм возвели в 1625 году в честь Казанской иконы Божией Матери — той самой, с которой ополчение Минина и Пожарского шло освобождать Москву. В 1936 году собор снесли по личному распоряжению Сталина: он мешал проведению парадов и демонстраций. На месте храма появился павильон, затем летнее кафе и даже общественный туалет. Спас собор архитектор Пётр Барановский: перед сносом он тайно сделал полные обмеры здания и сохранил чертежи. В конце 1980-х годов началось движение за восстановление храма; в 1990-м заложили первый камень, а в 1993-м освятили восстановленный собор. Это был первый в Москве храм, полностью воссозданный в советское и постсоветское время. Икону Казанской Божией Матери сюда вернули из Богоявленского собора в Елохове.\r\n', 'HISTORICAL FACT\r\n\r\nThe Kazan Cathedral on Red Square is a real “phoenix”: it was built twice and once completely destroyed. The first temple was erected in 1625 in honor of the Kazan Icon of the Mother of God - the same one with which the militia of Minin and Pozharsky went to liberate Moscow. In 1936, the cathedral was demolished on Stalin’s personal orders: it interfered with parades and demonstrations. A pavilion appeared on the site of the temple, then a summer cafe and even a public toilet. The architect Pyotr Baranovsky saved the cathedral: before demolition, he secretly took full measurements of the building and saved the drawings. In the late 1980s, a movement began to restore the temple; in 1990 the first stone was laid, and in 1993 the restored cathedral was consecrated. It was the first temple in Moscow to be completely recreated in Soviet and post-Soviet times. The icon of the Kazan Mother of God was returned here from the Epiphany Cathedral in Elokhov.', 1, 55.75527000, 37.61890900, 0, '2026-01-21 13:32:47', '2026-02-14 01:28:17', 1, NULL, 'ru', '📍 Точка №5 КАЗАНСКИЙ СОБОР\r\n👣 Куда идти:\r\n🚶 От Нулевого километра пройди через Воскресенские ворота. Поздравляю — ты на Красной площади!\r\n🏰 Как только выйдешь из-под арки ворот, сразу посмотри налево.\r\n🍭 Ты увидишь нарядный красно-белый храм с золотыми куполами — словно пряничный.\r\n\r\n🎯 Твоя цель:\r\nКАЗАНСКИЙ СОБОР.\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '📍 Point No. 5 KAZAN CATHEDRAL\r\n👣Where to go:\r\n🚶 From the Zero Kilometer, go through the Resurrection Gate. Congratulations - you are on Red Square!\r\n🏰 As soon as you come out from under the gate arch, immediately look to the left.\r\n🍭 You will see an elegant red and white temple with golden domes - like a gingerbread one.\r\n\r\n🎯 Your goal:\r\nKAZAN CATHEDRAL.\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(6, 1, 6, '📍 Точка №6: ГУМ (Главный Универсальный Магазин)', '📍 Point No. 6: GUM (Main Department Store)', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nДо революции здание называлось Верхние торговые ряды; строительство завершили в конце XIX века. Главная гордость ГУМа — стеклянная крыша-свод: её спроектировал гениальный инженер Владимир Шухов, создатель знаменитой Шуховской башни на Шаболовке. Конструкция из металлических арок и стёкол кажется невесомой, но на неё ушло более 800 тонн стали. Крыша держит огромные массы снега зимой и при этом пропускает столько света, что внутри днём почти не нужен искусственный свет. В советское время здесь был не только магазин: в 1922 году в ГУМе открыли первый государственный универмаг, в 1953-м после смерти Сталина здание едва не снесли — планировали построить гигантский монумент. В 1990-х ГУМ приватизировали; сейчас это престижный торговый центр с фонтаном в центре главной галереи — он работает с 1903 года и стал одним из символов Москвы.\r\n', 'HISTORICAL FACT\r\n\r\nBefore the revolution, the building was called the Upper Trading Rows; construction was completed at the end of the 19th century. The main pride of GUM is its glass vault roof: it was designed by the brilliant engineer Vladimir Shukhov, creator of the famous Shukhov Tower on Shabolovka. The structure of metal arches and glass seems weightless, but it took more than 800 tons of steel. The roof holds huge masses of snow in winter and at the same time lets in so much light that there is almost no need for artificial light inside during the day. In Soviet times, there was not only a store here: in 1922, the first state department store was opened in GUM; in 1953, after the death of Stalin, the building was almost demolished - they planned to build a giant monument. In the 1990s, GUM was privatized; now it is a prestigious shopping center with a fountain in the center of the main gallery - it has been operating since 1903 and has become one of the symbols of Moscow.', 1, 55.75530300, 37.61958600, 0, '2026-01-21 13:43:48', '2026-02-14 01:28:00', 1, NULL, 'ru', '👣 Куда идти:\r\n🚶 От Казанского собора пройди несколько десятков метров вдоль Красной площади — держись левой стороны.\r\n🏛️ ГУМ будет слева от тебя. Это большое монументальное здание с башенками и нарядными окнами, которое тянется вдоль всей стороны площади.\r\n\r\n🎯 Твоя цель:\r\nЦЕНТРАЛЬНЫЙ ВХОД (обращённый к Мавзолею/Казанскому собору)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🚶 From the Kazan Cathedral, walk a few tens of meters along Red Square - keep to the left.\r\n🏛️ GUM will be on your left. This is a large monumental building with turrets and elegant windows, which stretches along the entire side of the square.\r\n\r\n🎯 Your goal:\r\nCENTRAL ENTRANCE (facing the Mausoleum/Kazan Cathedral)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(7, 1, 7, '📍 Точка №7: Печатный двор (Никольская, 15)', '📍 Point No. 7: Printing Yard (Nikolskaya, 15)', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nЗдесь родилось русское книгопечатание. В 1564 году дьякон Иван Фёдоров и Пётр Мстиславец напечатали первую точно датированную русскую книгу — «Апостол». До этого книги переписывали от руки; печатный станок привёз в Москву Иван Грозный. На фасаде здания — герб с Львом и Единорогом: это личная эмблема царя, символ силы и мудрости. Солнечные часы над входом — одни из старейших в Москве; в ясный день по тени от гномона можно узнать время, правда, «древнемосковское», без учёта современных часовых поясов. Здание не раз горело и перестраивалось; нынешний облик в духе «нарышкинского барокко» оно получило в XVII веке. Сейчас здесь располагается Российский государственный гуманитарный университет (РГГУ). Внутри сохранились фрагменты старых палат; археологи находят здесь следы первых типографских мастерских.\r\n', 'HISTORICAL FACT\r\n\r\nRussian printing was born here. In 1564, Deacon Ivan Fedorov and Pyotr Mstislavets published the first accurately dated Russian book, The Apostle. Before this, books were copied by hand; The printing press was brought to Moscow by Ivan the Terrible. On the facade of the building there is a coat of arms with a Lion and a Unicorn: this is the personal emblem of the king, a symbol of strength and wisdom. The sundial above the entrance is one of the oldest in Moscow; on a clear day, you can tell the time from the shadow of the gnomon, although it is “ancient Moscow”, without taking into account modern time zones. The building burned and was rebuilt more than once; It received its current appearance in the spirit of the “Naryshkin Baroque” in the 17th century. Now the Russian State Humanitarian University (RGGU) is located here. Fragments of old chambers have been preserved inside; archaeologists find traces of the first printing workshops here.', 1, 55.75737400, 37.62248600, 0, '2026-01-21 13:50:42', '2026-02-14 01:27:53', 1, NULL, 'ru', '👣 Куда идти:\r\n🔄 Встань спиной к Красной площади (и ГУМу) и начинай идти прямо по Никольской улице. Это та самая улица, которая круглый год украшена «небесными» гирляндами.\r\n🚶 Иди прямо, проходя мимо входа в метро «Площадь Революции» — он будет слева.\r\n🏰 Продолжай идти, пока по левой стороне не увидишь необычное здание в готическом стиле: небесно-голубые стены, белые колонны и острые шпили.\r\n\r\n🎯 Твоя цель:\r\nПЕЧАТНЫЙ ДВОР\r\n(Остановись у фасада с большими солнечными часами)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🔄 Stand with your back to Red Square (and GUM) and start walking straight along Nikolskaya Street. This is the same street that is decorated with “heavenly” garlands all year round.\r\n🚶 Go straight, passing the entrance to the Ploshchad Revolyutsii metro station - it will be on the left.\r\n🏰 Continue walking until you see an unusual Gothic-style building on the left side: sky blue walls, white columns and sharp spiers.\r\n\r\n🎯 Your goal:\r\nPRINTING YARD\r\n(Stop by the façade with the big sundial)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(8, 1, 8, '📍 Точка №8: Третьяковский проезд', '📍 Point No. 8: Tretyakovsky passage', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nБратья Павел и Сергей Третьяковы — создатели знаменитой галереи — были не только меценатами, но и удачливыми купцами. В 1870 году они пробили в стене Китай-города проезд между Никольской и Театральным проездом: так появилась короткая улица-«прорубь», официально названная Третьяковским проездом. Чтобы окупить затраты, по обеим сторонам построили доходные дома с магазинами для сдачи в аренду. Архитектор Александр Каминский оформил проезд в едином стиле с арками и башенками. В советское время здесь были обычные магазины; с 2000-х годов проезд превратился в одну из самых дорогих торговых точек Москвы — здесь открыты бутики Louis Vuitton, Prada, Gucci и других люксовых брендов. У выхода из арки до сих пор висит синяя адресная табличка с историческим названием 🔵. В самом имени улицы зашифрован её тип — подсказка о назначении этого сквозного пути между двумя магистралями.\r\n', 'HISTORICAL FACT\r\n\r\nThe brothers Pavel and Sergei Tretyakov, the creators of the famous gallery, were not only philanthropists, but also successful merchants. In 1870, they made a passage in the wall of Kitai-Gorod between Nikolskaya and Teatralny Proezd: this is how a short “ice hole” street appeared, officially called Tretyakovsky Proezd. To recoup the costs, apartment buildings with shops for rent were built on both sides. Architect Alexander Kaminsky designed the passage in the same style with arches and turrets. In Soviet times there were ordinary shops here; Since the 2000s, the passage has turned into one of the most expensive retail outlets in Moscow - boutiques of Louis Vuitton, Prada, Gucci and other luxury brands are open here. At the exit of the arch there is still a blue address sign with the historical name 🔵. The street name itself encodes its type - a hint about the purpose of this end-to-end path between two highways.', 1, 55.75863600, 37.62341700, 0, '2026-01-21 13:55:57', '2026-02-14 01:27:47', 1, NULL, 'ru', '👣 Куда идти:\r\n🚶 Встань левым плечом к зданию Печатного двора (где ты нашёл Единорога) и продолжай идти по Никольской улице.\r\n🏰 Совсем скоро слева ты увидишь огромную каменную арку, похожую на вход в средневековый замок.\r\n\r\n🎯 Твоя цель:\r\nПРОЙТИ В АРКУ\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🚶 Stand with your left shoulder towards the Printing Yard building (where you found the Unicorn) and continue walking along Nikolskaya Street.\r\n🏰 Very soon you will see a huge stone arch on the left, similar to the entrance to a medieval castle.\r\n\r\n🎯 Your goal:\r\nGO INTO THE ARCH\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(9, 1, 9, '📍 Точка №9: Объект на Лубянской площади', '📍 Point No. 9: Object on Lubyanka Square', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nЦентральный детский магазин на Лубянке построили в 1957 году к VI Всемирному фестивалю молодёжи и студентов — первому крупному международному форуму в СССР после войны. Здание проектировал Алексей Душкин, автор станций метро «Маяковская» и «Кропоткинская». Огромные арочные окна должны были сделать массивное здание лёгким и «сказочным» — чтобы дети и гости фестиваля чувствовали праздник. Внутри установили гигантские механические часы: циферблат виден с улицы, механизм весит около 5 тонн и состоит из тысяч деталей. В советское время ЦДМ был главным детским магазином страны: здесь продавали игрушки, одежду, книги; на верхних этажах работали кружки и игровые зоны. После реконструкции 2010-х годов часы запустили заново; в здании снова открылся универмаг с детскими товарами и развлечениями. Над главным входом огромными буквами высечено полное название этого знакового для Москвы здания — москвичи давно сократили его до ёмкой аббревиатуры 🔠.\r\n', 'HISTORICAL FACT\r\n\r\nThe central children\'s store on Lubyanka was built in 1957 for the VI World Festival of Youth and Students - the first major international forum in the USSR after the war. The building was designed by Alexey Dushkin, the designer of the Mayakovskaya and Kropotkinskaya metro stations. Huge arched windows were supposed to make the massive building light and “fabulous” - so that children and festival guests could feel the holiday. A giant mechanical clock was installed inside: the dial is visible from the street, the mechanism weighs about 5 tons and consists of thousands of parts. In Soviet times, the Central Children\'s Store was the main children\'s store in the country: toys, clothes, books were sold here; There were clubs and play areas on the upper floors. After a renovation in the 2010s, the clock was restarted; A department store with children\'s goods and entertainment has reopened in the building. Above the main entrance, the full name of this iconic building for Moscow is carved in huge letters - Muscovites have long shortened it to a capacious abbreviation 🔠.', 1, 55.75943000, 37.62502900, 0, '2026-01-21 13:59:11', '2026-02-14 01:27:40', 1, NULL, 'ru', '👣 Куда идти:\r\n🏰 Выйди из Третьяковского проезда через арку к большой дороге — Театральному проезду.\r\n↗️ Поверни направо и иди вверх вдоль дороги.\r\n🏛️ Совсем скоро на противоположной стороне улицы ты увидишь монументальное здание, занимающее целый квартал. Его легко узнать по огромным арочным окнам.\r\n\r\n🎯 Твоя цель:\r\nПерейти дорогу и подойти к главному входу этого здания.\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n\r\n🏰 Exit Tretyakovsky Proezd through the arch to the main road (Teatralny Proezd).\r\n\r\n↗️ Turn right and go up along the road.\r\n\r\n🏛️ Very soon on the opposite side of the street you will see a monumental building occupying an entire block. It stands out with huge arched windows.\r\n\r\n----------------------------------------\r\n\r\n🎯 Your goal: Cross the road and approach the main entrance of this building.', NULL, NULL, 'text', '', '', 1, 3),
(10, 1, 10, '📍 Точка №10: Иоанн Богослов под Вязом', '📍 Point No. 10: John the Evangelist under the Elm', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nХрам Иоанна Богослова «под Вязом» — один из старейших в Москве. Название связано с огромным вязом, который рос перед церковью ещё в XVI–XVII веках: тогда адресов в современном виде не было, и люди говорили «у церкви под вязом». Дерево спилили в 1775 году из-за ветхости. Здание не раз горело и перестраивалось; на информационной табличке на стене указаны две даты — год постройки или перестройки и год освящения; разница между ними говорит о непростой истории храма. В советское время здесь размещался Музей истории и реконструкции Москвы; богослужения возобновились только в 1992 году. Храм стоит прямо на линии улицы, без ограды — будто встроен в ряд домов. Рядом — знаменитая вывеска «Не рыба» (кафе), по которой москвичи ориентируются при встрече.\r\n', 'HISTORICAL FACT\r\n\r\nThe Church of St. John the Evangelist “under Elm” is one of the oldest in Moscow. The name is associated with a huge elm tree that grew in front of the church back in the 16th–17th centuries: then there were no addresses in the modern form, and people said “by the church under the elm tree.” The tree was cut down in 1775 due to dilapidation. The building burned and was rebuilt more than once; the information plaque on the wall shows two dates - the year of construction or reconstruction and the year of consecration; the difference between them speaks of the complex history of the temple. During Soviet times, the Museum of History and Reconstruction of Moscow was located here; services resumed only in 1992. The temple stands directly on the street line, without a fence - as if it was built into a row of houses. Nearby is the famous “Not a Fish” (cafe) sign, which Muscovites use to orient themselves when meeting.', 1, 55.75759000, 37.62782400, 0, '2026-01-21 14:03:21', '2026-02-14 01:27:34', 1, NULL, 'ru', '👣 Куда идти:\r\n🏢 Продолжай движение в том же направлении. Здание ЦДМ должно оставаться по левую руку — переходить улицу не нужно.\r\n🚶 Иди прямо по своей стороне улицы. Дойдя до углового здания с большими витринами и ироничной вывеской про то, что здесь не подают то, что обычно плавает 🐟, плавно поверни направо — но продолжай идти по большой улице, не сворачивая в переулки.\r\n🏛️ Двигайся дальше прямо. Слева появится большое светло-жёлтое здание со строгим фасадом — здание ФСБ. Оно находится напротив входа в метро «Лубянская площадь».\r\n🚇 Пройди мимо входа в метро, оставив его по пути. Улицу по-прежнему переходить не нужно.\r\n⛪ Через несколько десятков метров справа, прямо вдоль большой улицы, ты увидишь небольшую старинную церковь. Она стоит на линии домов, без сквера и ограды.\r\n\r\n🎯 Твоя цель:\r\nХРАМ ИОАННА БОГОСЛОВА ПОД ВЯЗОМ\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🏢 Continue moving in the same direction. The Central Children\'s House building should remain on the left - there is no need to cross the street.\r\n🚶 Walk straight on your side of the street. Having reached the corner building with large shop windows and an ironic sign about the fact that they don’t serve what usually floats here 🐟, turn smoothly to the right - but continue to walk along the main street without turning into alleys.\r\n🏛️ Move on straight ahead. A large light yellow building with a strict facade will appear on the left - the FSB building. It is located opposite the entrance to the Lubyanka Square metro station.\r\n🚇 Walk past the entrance to the subway, leaving it along the way. There is still no need to cross the street.\r\n⛪ After a few tens of meters on the right, straight along the big street, you will see a small old church. It stands on the line of houses, without a park or fence.\r\n\r\n🎯 Your goal:\r\nTEMPLE OF JOHN THE THEOLOGIST UNDER THE ELM\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(11, 1, 11, '📍 Точка №11: Метро «Китай-город»', '📍 Point No. 11: Metro “Kitay-Gorod”', NULL, '💡 ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\n🚉 Уникальная станция: «Китай-город» — одна из немногих в мире пересадок кросс-платформенного типа. Это значит, что поезда разных линий приходят на одну платформу. Чтобы пересесть, не нужно бегать по длинным переходам — достаточно просто перейти на другую сторону зала.\r\n\r\n🎨 Визуальный код: Цветные полосы под буквой «М» на входе — это «язык» метрополитена. Они придуманы для того, чтобы ты сразу понял, на какие ветки попадешь, еще до того, как спустишься вниз и заглянешь в карту.', '💡 HISTORICAL FACT\r\n\r\n🚉 Unique station: “Kitai-Gorod” is one of the few cross-platform transfers in the world. This means that trains from different lines arrive at the same platform. To change seats, you don’t need to run along long passages - you just need to go to the other side of the hall.\r\n\r\n🎨 Visual code: The colored stripes under the letter “M” at the entrance are the “language” of the metro. They were invented so that you immediately understand which branches you will end up on, even before you go down and look at the map.', 1, 55.75666700, 37.62944100, 0, '2026-01-21 14:07:33', '2026-02-14 01:27:21', 1, NULL, 'ru', '👣 Куда идти:\r\n⛪ Продолжай идти в том же направлении, что и раньше — оставь розовый храм по правую руку и двигайся дальше вперёд.\r\n📉 Иди прямо по Новой площади — это та самая широкая улица, по которой ты уже шёл. Она плавно уходит вниз в сторону центра.\r\n🏮 Ищи знакомую красную букву «М» — она станет твоим маяком.\r\n\r\n🎯 Твоя цель:\r\nВХОД В МЕТРО «КИТАЙ-ГОРОД»\r\n(Остановись перед буквой «М», не спускаясь)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n⛪ Continue walking in the same direction as before - leave the pink temple on your right hand and move further forward.\r\n📉 Walk straight along New Square - this is the same wide street along which you have already walked. It smoothly goes down towards the center.\r\n🏮 Look for the familiar red letter “M” - it will become your beacon.\r\n\r\n🎯 Your goal:\r\nENTRANCE TO THE KITAY-GOROD METRO\r\n(Stop in front of the letter “M” without going down)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(12, 1, 12, '📍 Точка №12: Часовня-памятник «Героям Плевны»', '📍 Point No. 12: Chapel-monument to the “Heroes of Plevna”', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nЧасовня-памятник гренадёрам, павшим под болгарским городом Плевной в 1877 году во время Русско-турецкой войны, установлена в 1887 году на средства оставшихся в живых однополчан. Памятник полностью отлит из чугуна; на вершине шатра — православный крест, под ним — полумесяц (символ побеждённой Османской империи). В народе памятник прозвали «У Хвоста»: в советское время здесь была конечная остановка автобусов и маршруток, и очередь пассажиров огибала часовню, напоминая длинный хвост. Фраза «Встретимся у хвоста» на десятилетия стала паролем для встреч москвичей. Внутри часовни когда-то горела лампада; сейчас там мемориальные плиты с именами павших. Памятник стоит в Ильинском сквере; к нему ведёт выход №4 из перехода метро «Китай-город».', 'HISTORICAL FACT\r\n\r\nThe chapel-monument to the grenadiers who fell near the Bulgarian city of Plevna in 1877 during the Russian-Turkish War was erected in 1887 at the expense of surviving fellow soldiers. The monument is entirely cast from cast iron; on top of the tent there is an Orthodox cross, under it there is a crescent (a symbol of the defeated Ottoman Empire). People nicknamed the monument “At the Tail”: in Soviet times, there was a final stop for buses and minibuses, and the line of passengers went around the chapel, resembling a long tail. The phrase “Meet me at the tail” became the password for meetings among Muscovites for decades. A lamp once burned inside the chapel; Now there are memorial plaques with the names of the fallen. The monument stands in Ilyinsky Park; Exit No. 4 leads to it from the Kitay-Gorod metro crossing.', 1, 55.75669400, 37.63118900, 0, '2026-01-21 14:10:23', '2026-02-14 01:27:14', 1, NULL, 'ru', '👣 Куда идти:\r\n🚇 Спустись в переход метро. Как только спустишься вниз — сразу поверни налево и держись правой стороны туннеля.\r\n🔀 Иди прямо и найди указатель «Выход №4». Поднимайся по лестнице — и ты окажешься у начала Ильинского сквера.\r\n🗼 Перед тобой появится высокая чёрная башня необычной формы.\r\n\r\n🎯 Твоя цель:\r\nПАМЯТНИК ГЕРОЯМ ПЛЕВНЫ\r\n(Подойди вплотную к часовне)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🚇 Go down to the metro crossing. As soon as you go down, immediately turn left and stay on the right side of the tunnel.\r\n🔀 Go straight and find the sign “Exit No. 4”. Climb the stairs and you will find yourself at the beginning of Ilyinsky Square.\r\n🗼 A tall black tower of an unusual shape will appear in front of you.\r\n\r\n🎯 Your goal:\r\nMONUMENT TO THE HEROES OF PLEVNA\r\n(Come close to the chapel)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(13, 1, 13, '📍 Точка №13: Кирилл и Мефодий', '📍 Point No. 13: Cyril and Methodius', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nПамятник святым равноапостольным Кириллу и Мефодию установлен в 1992 году в Ильинском сквере. Братья-просветители создали славянскую азбуку (кириллицу) в IX веке и перевели на славянский язык богослужебные книги. У подножия памятника горит Неугасимая лампада — символ света знаний и просвещения. На постаменте высечена торжественная надпись с посвящением святым братьям от всей России 📜. Одно ключевое слово в ней описывает отношение страны к великим просветителям — прочитай надпись внимательно, чтобы его найти. Каждый год 24 мая, в День славянской письменности и культуры, от памятника начинается большой крестный ход. До революции студенты московских университетов приходили сюда перед экзаменами — просить удачи у «учителей славян».\r\n', 'HISTORICAL FACT\r\n\r\nThe monument to Saints Cyril and Methodius, Equal to the Apostles, was erected in 1992 in Ilyinsky Park. The enlightenment brothers created the Slavic alphabet (Cyrillic alphabet) in the 9th century and translated liturgical books into the Slavic language. At the foot of the monument there is an unquenchable lamp burning - a symbol of the light of knowledge and enlightenment. A solemn inscription with dedication to the holy brothers from all of Russia is carved on the pedestal 📜. One key word in it describes the country\'s attitude towards the great educators - read the inscription carefully to find it. Every year on May 24, the Day of Slavic Literature and Culture, a large religious procession begins from the monument. Before the revolution, students from Moscow universities came here before exams to ask for good luck from the “teachers of the Slavs.”', 1, 55.75459300, 37.63392900, 0, '2026-01-21 14:13:09', '2026-02-14 01:27:07', 1, NULL, 'ru', '👣 Куда идти:\r\n⛪ Обогни чёрную часовню и продолжай идти вниз по Ильинскому скверу — по главной аллее, уходящей под уклон.\r\n📜 Через пару минут перед тобой появится памятник двум старцам в монашеских рясах.\r\n\r\n🎯 Твоя цель:\r\nПАМЯТНИК КИРИЛЛУ И МЕФОДИЮ\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n⛪ Go around the black chapel and continue walking down Ilyinsky Square - along the main alley that goes downhill.\r\n📜 In a couple of minutes, a monument to two elders in monastic robes will appear in front of you.\r\n\r\n🎯 Your goal:\r\nMONUMENT TO CYRILL AND MEFODIUS\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(14, 1, 14, '📍 Точка №14: Церковь Всех Святых на Кулишках', '📍 Point No. 14: Church of All Saints on Kulishki', NULL, 'ИСТОРИЧЕСКИЙ ФАКТ\r\n\r\nТы в самом сердце «куличек» — отсюда пошло выражение «у чёрта на куличках» (то есть очень далеко). «Кулишками» в старину называли болотистые места или вырубки в лесу; здесь когда-то было болото у стен Белого города. В XVII веке в этой церкви, по легенде, завёлся беспокойный дух: прихожане жаловались на стуки, летающие предметы и огни. С тех пор и пошла поговорка про чёрта на Кулишках. Колокольня храма хранит свою архитектурную загадку 🔔: из-за размыва грунта древними подземными ручьями фундамент претерпел серьёзные изменения. Внимательный наблюдатель сразу заметит необычную особенность, сравнив силуэт колокольни с линиями соседних зданий. Храм один из старейших в Москве; не раз горел и перестраивался. С площади Варварские Ворота открывается лучший вид на колокольню — туристы часто фотографируются на её фоне, пытаясь разгадать секрет этого древнего сооружения.\r\n', 'HISTORICAL FACT\r\n\r\nYou are in the very heart of the “Kulichek” - this is where the expression “in the middle of nowhere” comes from (that is, very far away). In the old days, swampy places or clearings in the forest were called “Kulishki”; there once was a swamp near the walls of the White City. In the 17th century, according to legend, a restless spirit arose in this church: parishioners complained about knocking, flying objects and lights. Since then, the saying about the devil in Kulishki began. The bell tower of the temple keeps its architectural mystery 🔔: due to the erosion of the soil by ancient underground streams, the foundation has undergone serious changes. An attentive observer will immediately notice an unusual feature by comparing the silhouette of the bell tower with the lines of neighboring buildings. The temple is one of the oldest in Moscow; It burned and was rebuilt more than once. The best view of the bell tower opens from Varvarskie Vorota Square - tourists often take pictures against its background, trying to unravel the secret of this ancient structure.', 1, 55.75365900, 37.63494000, 0, '2026-01-21 14:15:23', '2026-02-14 01:27:00', 1, NULL, 'ru', '👣 Куда идти:\r\n🌲 Спускайся по Ильинскому скверу до самого конца — к площади Варварские Ворота.\r\n🚶 Перейди дорогу по пешеходному переходу к высокому зданию из красного кирпича с заметной колокольней.\r\n\r\n🎯 Твоя цель:\r\nХРАМ ВСЕХ СВЯТЫХ НА КУЛИШКАХ\r\n(Встань так, чтобы хорошо видеть колокольню)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n🌲 Go down Ilyinsky Square to the very end - to Varvarskie Vorota Square.\r\n🚶 Cross the road at the pedestrian crossing to a tall red brick building with a noticeable bell tower.\r\n\r\n🎯 Your goal:\r\nTEMPLE OF ALL SAINTS ON KULISHKI\r\n(Stand so that you can clearly see the bell tower)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(15, 1, 15, '📍 Точка №15 НАБЕРЕЖНАЯ (ПАРК «ЗАРЯДЬЕ»)', '📍 Point No. 15 EMBANKMENT (ZARYADYE PARK)', NULL, 'ИНТЕРЕСНЫЙ ФАКТ\r\n\r\nДо 2006 года на месте парка «Зарядье» стояла гостиница «Россия» — одна из крупнейших в мире. В ней было более 3000 номеров, огромный кинотеатр «Заря», концертный зал на 2500 мест, рестораны, парикмахерские и даже отдельный пост милиции. Гостиницу построили в 1967 году на месте снесённых кварталов старого Зарядья; её фасад тянулся вдоль Москвы-реки на сотни метров. В 1977 году в «России» произошёл сильный пожар; погибли люди. В 2000-х здание признали аварийным и решили снести. Обломков бетона и арматуры хватило бы на целый микрорайон; их вывозили месяцами. На освободившемся месте разбили парк «Зарядье» с набережной, откуда открывается панорама Кремля и Москвы-реки.\r\n', 'INTERESTING FACT\r\n\r\nUntil 2006, on the site of Zaryadye Park there stood the Rossiya Hotel, one of the largest in the world. It had more than 3,000 rooms, a huge Zarya cinema, a concert hall with 2,500 seats, restaurants, hairdressers and even a separate police post. The hotel was built in 1967 on the site of the demolished quarters of the old Zaryadye; its facade stretched along the Moscow River for hundreds of meters. In 1977, there was a severe fire in “Russia”; people died. In the 2000s, the building was declared unsafe and decided to demolish. There would be enough fragments of concrete and reinforcement for an entire microdistrict; they were taken out for months. The Zaryadye Park was built on the vacant site, with an embankment offering a panoramic view of the Kremlin and the Moscow River.', 1, 55.74970300, 37.63253400, 0, '2026-01-21 15:14:45', '2026-02-14 01:26:54', 1, NULL, 'ru', '👣 Куда идти:\r\n1. Оставь храм Всех Святых по левую руку, а памятник — по правую.\r\n2. Двигайся прямо от церкви. Впереди будет поворот налево — поверни там.\r\n3. После поворота, на другой стороне дороги, появится Китайгородская стена.\r\n4. Продолжай идти прямо вдоль неё и выходи к широкой прогулочной зоне у воды.\r\n\r\n🎯 Твоя цель:\r\nНАБЕРЕЖНАЯ ПАРКА «ЗАРЯДЬЕ»\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. Leave the Church of All Saints on your left hand, and the monument on your right.\r\n2. Move straight from the church. There will be a left turn ahead - turn there.\r\n3. After the turn, on the other side of the road, the Kitai-Gorod wall will appear.\r\n4. Continue straight along it and come out to a wide walking area near the water.\r\n\r\n🎯 Your goal:\r\nEMBANKMENT PARK \"ZARYADYE\"\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3);
INSERT INTO `points` (`id`, `route_id`, `order`, `name`, `name_en`, `address`, `fact_text`, `fact_text_en`, `min_people`, `latitude`, `longitude`, `is_free`, `created_at`, `updated_at`, `audio_enabled`, `audio_file_path`, `audio_language`, `audio_text`, `audio_text_en`, `audio_file_path_ru`, `audio_file_path_en`, `task_type`, `text_answer`, `text_answer_hint`, `accept_partial_match`, `max_attempts`) VALUES
(16, 1, 16, '📍 Точка №16 ЗАРЯДЬЕ (СТУПЕНИ)', '📍 Point No. 16 CHARGE (STAGES)', NULL, 'ИНТЕРЕСНЫЙ ФАКТ\r\n\r\nПарк «Зарядье» открыли в 2017 году на месте снесённой гостиницы «Россия». Его главная идея — «природный ландшафт»: архитекторы воссоздали четыре природные зоны России — тундру, степь, лес и болото — в одном месте в центре Москвы. Растения подобрали так, чтобы они выживали в городском климате; в степной зоне растут ковыль и полынь, в лесной — берёзы и ели. Большая лестница у стеклянного павильона «Заповедное посольство» ведёт от набережной вглубь парка; точное количество ступеней — предмет для загадки 🧩. Ступени широкие и пологие; с них открывается вид на Москву-реку и Кремль. Парк спроектировало международное бюро Diller Scofidio + Renfro; он стал одним из символов обновлённой Москвы.', 'INTERESTING FACT\r\n\r\nZaryadye Park was opened in 2017 on the site of the demolished Rossiya Hotel. Its main idea is “natural landscape”: the architects recreated four natural zones of Russia - tundra, steppe, forest and swamp - in one place in the center of Moscow. Plants were selected to survive in urban climates; Feather grass and wormwood grow in the steppe zone, and birch and spruce grow in the forest zone. A large staircase at the glass pavilion “Reserve Embassy” leads from the embankment deep into the park; the exact number of steps is a mystery 🧩. The steps are wide and flat; they offer views of the Moscow River and the Kremlin. The park was designed by the international bureau Diller Scofidio + Renfro; it became one of the symbols of the renewed Moscow.', 1, 55.75052000, 37.63144100, 0, '2026-01-21 15:16:04', '2026-02-14 01:26:46', 1, NULL, 'ru', '👣 Куда идти:\r\n1. Встань на набережной лицом к Москве-реке.\r\n2. Поверни направо.\r\n3. Иди по пешеходной дорожке вдоль воды. Впереди будет знакомое большое стеклянное здание.\r\n4. Подойди к зданию. Большая лестница находится с другой стороны — со стороны дороги.\r\n\r\n🎯 Твоя цель:\r\nБОЛЬШАЯ ЛЕСТНИЦА В ПАРКЕ «ЗАРЯДЬЕ»\r\n(Встань у основания лестницы)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. Stand on the embankment facing the Moscow River.\r\n2. Turn right.\r\n3. Walk along the walking path along the water. There will be a familiar large glass building ahead.\r\n4. Approach the building. The large staircase is on the other side - on the side of the road.\r\n\r\n🎯 Your goal:\r\nGREAT STAIRWAY IN ZARYADYE PARK\r\n(Stand at the bottom of the stairs)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(17, 1, 17, '📍 Точка №17 ПАРЯЩИЙ МОСТ (ПАРК «ЗАРЯДЬЕ»)', '📍 Point No. 17 FLOATING BRIDGE (ZARYADYE PARK)', NULL, 'ИНТЕРЕСНЫЙ ФАКТ\r\n\r\nПарящий мост — одна из главных достопримечательностей парка «Зарядье». Консольная конструкция нависает над Москвой-рекой на 70 метров; под мостом нет ни одной опоры — создаётся ощущение, что он «парит» над водой. С моста открывается панорама Кремля, собора Василия Блаженного и набережных. До 2006 года на месте всего парка стояла гостиница «Россия» — одна из крупнейших в мире (более 3000 номеров), со своим кинотеатром и концертным залом. Мост построили в 2017 году; его конструкция выдерживает ветер до 40 м/с и рассчитана на тысячи посетителей. Туристы любят делать здесь селфи на фоне Кремля; вечером мост подсвечивается. Это одна из лучших смотровых точек Москвы.\r\n', 'INTERESTING FACT\r\n\r\nThe floating bridge is one of the main attractions of Zaryadye Park. The cantilever structure hangs 70 meters over the Moscow River; There is not a single support under the bridge - it feels like it is “floating” above the water. From the bridge there is a panoramic view of the Kremlin, St. Basil\'s Cathedral and embankments. Until 2006, on the site of the entire park stood the Rossiya Hotel, one of the largest in the world (more than 3,000 rooms), with its own cinema and concert hall. The bridge was built in 2017; its design can withstand winds of up to 40 m/s and is designed to accommodate thousands of visitors. Tourists love to take selfies here with the Kremlin in the background; In the evening the bridge is illuminated. This is one of the best observation points in Moscow.', 1, 55.74942800, 37.62946700, 0, '2026-01-21 15:38:36', '2026-02-14 01:26:38', 1, NULL, 'ru', '👣 Куда идти:\r\n1. Поднимись по лестнице и остановись наверху.\r\n2. Прямо перед тобой будет Парящий мост — бетонная площадка, уходящая над рекой.\r\n3. Поверни направо и иди по дорожке к входу на мост.\r\n4. Выйди на мост и двигайся до самой дальней точки.\r\n\r\n🎯 Твоя цель:\r\nПАРЯЩИЙ МОСТ\r\n(Дойди до самой высокой точки)\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. Go up the stairs and stop at the top.\r\n2. Directly in front of you will be the Floating Bridge - a concrete platform extending over the river.\r\n3. Turn right and follow the path to the bridge entrance.\r\n4. Get out onto the bridge and move to the farthest point.\r\n\r\n🎯 Your goal:\r\nFLOATING BRIDGE\r\n(Reach to the highest point)\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(18, 1, 18, '📍 Точка №18 КРАСНАЯ ПЛОЩАДЬ, 5 (СРЕДНИЕ ТОРГОВЫЕ РЯДЫ)', '📍 Point No. 18 RED SQUARE, 5 (MIDDLE TRADE ROWS)', NULL, 'ИНТЕРЕСНЫЙ ФАКТ\r\n\r\nЖёлтое здание на углу Красной площади и Васильевского спуска — Средние торговые ряды. Их построили в 1891–1893 годах по проекту архитектора Романа Клейна в едином стиле с Верхними торговыми рядами (ныне ГУМ). Раньше на этом месте стояли старые лавки; их снесли и возвели новое здание в псевдорусском стиле с башенками и кокошниками. Ряды служили главным торговым центром Москвы: здесь продавали ткани, одежду, посуду. В советское время в здании размещались учреждения и магазины; сейчас там офисы и бутики. Здание образует угол между Красной площадью и парком «Зарядье»; от него открывается вид на собор Василия Блаженного и Кремль. Это часть исторической застройки, охраняемой ЮНЕСКО.', 'INTERESTING FACT\r\n\r\nThe yellow building on the corner of Red Square and Vasilyevsky Spusk is the Middle Trading Rows. They were built in 1891–1893 according to the design of the architect Roman Klein in the same style as the Upper Trading Rows (now GUM). Previously, there were old shops on this site; they were demolished and a new building was erected in the pseudo-Russian style with turrets and kokoshniks. The rows served as the main shopping center of Moscow: fabrics, clothes, and dishes were sold here. During Soviet times, the building housed institutions and shops; now there are offices and boutiques. The building forms a corner between Red Square and Zaryadye Park; it offers views of St. Basil\'s Cathedral and the Kremlin. This is part of a historical building protected by UNESCO.', 1, 55.75244900, 37.62402700, 0, '2026-01-21 15:39:45', '2026-02-14 01:26:30', 1, NULL, 'ru', '👣 Куда идти:\r\n1. Встань на Парящем мосту спиной к реке и возвращайся обратно в парк «Зарядье».\r\n2. Иди по дорожке в сторону Кремля, двигаясь ближе к исторической застройке.\r\n3. По пути появится жёлтое здание у перекрёстка — это ориентир.\r\n4. Пройди между жёлтым зданием и храмом Василия Блаженного.\r\n\r\n🎯 Твоя цель:\r\nЖЁЛТОЕ ЗДАНИЕ (СРЕДНИЕ ТОРГОВЫЕ РЯДЫ)\r\n(Подойди к зданию)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. Stand on the Floating Bridge with your back to the river and return back to Zaryadye Park.\r\n2. Walk along the path towards the Kremlin, moving closer to the historical buildings.\r\n3. Along the way, a yellow building will appear at the intersection - this is a landmark.\r\n4. Walk between the yellow building and St. Basil\'s Cathedral.\r\n\r\n🎯 Your goal:\r\nYELLOW BUILDING (MIDDLE TRADE RANKS)\r\n(Come to the building)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3),
(19, 1, 19, '📍 Точка №19 КРАСНАЯ ПЛОЩАДЬ И СОБОР ВАСИЛИЯ БЛАЖЕННОГО (ФИНАЛ)', '📍 Point No. 19 RED SQUARE AND ST. BASILY\'S CATHEDRAL (FINAL)', NULL, 'ИНТЕРЕСНЫЙ ФАКТ\r\n\r\nСобор Покрова Пресвятой Богородицы на Рву, в народе — храм Василия Блаженного, построили в 1555–1561 годах по приказу Ивана Грозного в честь взятия Казани. На самом деле это не один храм, а одиннадцать церквей на общем основании: в центре — церковь Покрова, вокруг — восемь приделов, плюс колокольня и придел Василия Блаженного (юродивого, похороненного у стен). Легенда про ослепление архитекторов Постника и Бармы — миф: после собора они участвовали в других постройках. Памятник Минину и Пожарскому — первый скульптурный монумент Москвы 🗿 — изначально стоял перед Верхними торговыми рядами (ГУМ), но в 1930-х его перенесли к собору, чтобы не мешал парадам. На гранитном постаменте высечена торжественная надпись с посвящением героям, а дата установки записана старинным способом — буквами. Собор — объект ЮНЕСКО и один из символов России.\r\n', 'INTERESTING FACT\r\n\r\nThe Cathedral of the Intercession of the Blessed Virgin Mary on the Moat, popularly known as St. Basil\'s Cathedral, was built in 1555–1561 by order of Ivan the Terrible in honor of the capture of Kazan. In fact, this is not one temple, but eleven churches on a common basis: in the center is the Church of the Intercession, around there are eight chapels, plus a bell tower and the chapel of St. Basil the Blessed (the holy fool, buried near the walls). The legend about the blinding of the architects Postnik and Barma is a myth: after the cathedral, they participated in other buildings. The monument to Minin and Pozharsky - the first sculptural monument in Moscow 🗿 - originally stood in front of the Upper Trading Rows (GUM), but in the 1930s it was moved to the cathedral so as not to interfere with parades. A solemn inscription with dedication to the heroes is carved on the granite pedestal, and the installation date is written in the old way - in letters. The cathedral is a UNESCO site and one of the symbols of Russia.', 1, 55.75282500, 37.62260000, 0, '2026-01-21 15:41:41', '2026-02-14 01:26:21', 1, NULL, 'ru', '👣 Куда идти:\r\n1. От жёлтого здания двигайся в сторону Красной площади.\r\n2. Иди прямо, оставляя парк «Зарядье» позади.\r\n3. Выйди к перекрёстку между Собором Василия Блаженного (справа) и Средними торговыми рядами (слева).\r\n4. Продолжай идти вперёд и остановись прямо перед фасадом собора.\r\n\r\n🎯 Твоя цель:\r\nСОБОР ВАСИЛИЯ БЛАЖЕННОГО\r\n(Окажись на Васильевском спуске прямо перед входом в храм)\r\n\r\n----------------------------------------\r\n\r\nКогда будешь на месте, нажми кнопку:\r\n👇 [ Я НА МЕСТЕ ]', '👣Where to go:\r\n1. From the yellow building, move towards Red Square.\r\n2. Go straight, leaving Zaryadye Park behind.\r\n3. Go to the intersection between St. Basil\'s Cathedral (on the right) and the Middle Shopping Rows (on the left).\r\n4. Continue forward and stop right in front of the cathedral façade.\r\n\r\n🎯 Your goal:\r\nBASIL\'S CATHEDRAL\r\n(Be on Vasilievsky Spusk right in front of the entrance to the temple)\r\n\r\n----------------------------------------\r\n\r\nWhen you are there, press the button:\r\n👇 [I\'M HERE]', NULL, NULL, 'photo', '', '', 1, 3);

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
-- Структура таблицы `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `option_a` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_b` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_b_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_c_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_d` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_d_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_option` enum('a','b','c','d') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `progress_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `correct_count` int NOT NULL DEFAULT '0',
  `total_count` int NOT NULL DEFAULT '0',
  `reward_given` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Структура таблицы `referral_levels`
--

CREATE TABLE `referral_levels` (
  `id` int UNSIGNED NOT NULL,
  `level` int UNSIGNED NOT NULL COMMENT 'Номер уровня (1, 2, 3, 4)',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название уровня',
  `name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название на английском',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание награды',
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание на английском',
  `required_referrals` int UNSIGNED NOT NULL COMMENT 'Необходимое количество рефералов',
  `reward_type` enum('tokens_per_referral','discount_code','percent_of_sales','free_route','special') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип награды',
  `reward_value` decimal(10,2) DEFAULT NULL COMMENT 'Значение награды (гроши/процент)',
  `icon` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '?' COMMENT 'Иконка уровня',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Уровень активен',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Уровни реферальной программы';

--
-- Дамп данных таблицы `referral_levels`
--

INSERT INTO `referral_levels` (`id`, `level`, `name`, `name_en`, `description`, `description_en`, `required_referrals`, `reward_type`, `reward_value`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Начало пути', 'Getting Started', '20 грошей за каждого приглашённого друга', '20 tokens for each invited friend', 3, 'tokens_per_referral', 20.00, '🌱', 1, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(2, 2, 'Активный участник', 'Active Participant', 'Промокод на 15% скидки', '15% discount promo code', 10, 'discount_code', 15.00, '🔥', 1, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(3, 3, 'Главный фанат', 'Super Fan', 'Бесплатный квест на выбор', 'Free quest of your choice', 30, 'free_route', 0.00, '🏆', 1, '2026-02-05 20:20:17', '2026-02-05 20:20:17'),
(4, 4, 'Официальный партнёр', 'Official Partner', 'Экскурсия в подарок + особый статус', 'Free tour + special status', 100, 'special', 0.00, '👑', 1, '2026-02-05 20:20:17', '2026-02-05 20:20:17');

-- --------------------------------------------------------

--
-- Структура таблицы `referral_rewards`
--

CREATE TABLE `referral_rewards` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID владельца реферальной ссылки',
  `referral_id` int UNSIGNED NOT NULL COMMENT 'ID приглашённого пользователя',
  `level` int UNSIGNED NOT NULL COMMENT 'Уровень на момент награды',
  `reward_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип награды',
  `reward_amount` decimal(10,2) DEFAULT NULL COMMENT 'Сумма награды',
  `promo_code_id` int UNSIGNED DEFAULT NULL COMMENT 'ID выданного промокода (если есть)',
  `route_id` int UNSIGNED DEFAULT NULL COMMENT 'ID подаренного маршрута (если есть)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='История реферальных наград';

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
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Скрыт администратором',
  `reward_given` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Бонус за отзыв начислен',
  `reward_amount` decimal(10,2) DEFAULT NULL COMMENT 'Сумма начисленного бонуса'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `route_id`, `progress_id`, `rating`, `text`, `created_at`, `updated_at`, `is_approved`, `is_hidden`, `reward_given`, `reward_amount`) VALUES
(1, 2, 1, 1, 4, 'Очень атмосферный маршрут, всё понравилось, но иногда фото распознаются не с первого раза. В целом ок, будем ещё ходить.', '2026-01-26 15:12:33', '2026-01-26 15:12:33', 1, 0, 0, NULL),
(2, 3, 1, 2, 5, 'Super experience in the center of Moscow! Tasks are clear, hints are helpful, photos check works great. Recommended!', '2026-01-27 16:45:02', '2026-01-27 16:45:02', 1, 0, 0, NULL),
(3, 4, 1, 3, 5, 'Очень крутой формат прогулки, будто играешь в квест в реальном городе. Голосовой гид и факты прям в тему.', '2026-01-29 15:28:17', '2026-01-29 15:28:17', 1, 0, 0, NULL),
(4, 5, 1, 4, 5, 'Очень понравилась структура маршрута и заданий. Без багов, проверка фото быстрая, админы отвечают оперативно.', '2026-01-30 16:03:41', '2026-01-30 16:03:41', 1, 0, 0, NULL),
(5, 6, 1, 5, 5, 'Прошли маршрут как семейную прогулку. Дети в восторге от заданий и загадок, взрослым тоже было интересно.', '2026-02-01 15:47:22', '2026-02-01 15:47:22', 1, 0, 0, NULL),
(6, 7, 1, 6, 5, 'Отличный баланс прогулки, истории и фана. Аудиогид с живым голосом — огромный плюс.', '2026-02-02 16:19:08', '2026-02-02 16:19:08', 1, 0, 0, NULL),
(7, 8, 1, 7, 5, 'Отличный способ посмотреть центр Москвы без скучных экскурсий. Квест держит внимание до конца.', '2026-02-04 15:55:14', '2026-02-04 15:55:14', 1, 0, 0, NULL),
(8, 9, 1, 8, 5, 'Фото‑задания забавные, проверка работает уверенно. Гида не нужно — бот сам всё ведёт.', '2026-02-05 16:31:39', '2026-02-05 16:31:39', 1, 0, 0, NULL),
(9, 10, 1, 9, 5, 'Очень понравился маршрут: продуманные точки, красивые виды, понятные подсказки. 5/5.', '2026-02-06 15:08:51', '2026-02-06 15:08:51', 1, 0, 0, NULL),
(10, 11, 1, 10, 5, 'Проходили как тимбилдинг. Всем зашло, особенно сочетание загадок и проверки фото.', '2026-02-07 16:22:27', '2026-02-07 16:22:27', 1, 0, 0, NULL),
(11, 12, 1, 11, 5, 'Крутая идея — получать достижения и сертификат за прохождение. Чувствуется завершённый продукт.', '2026-02-09 15:41:03', '2026-02-09 15:41:03', 1, 0, 0, NULL),
(12, 13, 1, 12, 5, 'Всё работает плавно: оплаты, подсказки, фото, личный кабинет на сайте. Удобный интерфейс.', '2026-02-10 16:14:56', '2026-02-10 16:14:56', 1, 0, 0, NULL),
(13, 14, 1, 13, 5, 'Маршрут сделали вечером после работы, устали, но довольны. Узнали много нового про центр Москвы.', '2026-02-11 15:33:18', '2026-02-11 15:33:18', 1, 0, 0, NULL),
(14, 15, 1, 14, 5, 'Понятный вход в квест, инструкции без воды. Бот ведёт шаг за шагом, заблудиться невозможно.', '2026-02-12 16:07:42', '2026-02-12 16:07:42', 1, 0, 0, NULL),
(15, 16, 1, 15, 5, 'Очень красиво построены подсказки: сначала лёгкие намёки, потом детальные подсказки. Баланс отличный.', '2026-02-13 15:52:29', '2026-02-13 15:52:29', 1, 0, 0, NULL),
(16, 17, 1, 16, 5, 'Круто, что всё внутри Telegram плюс сайт — не нужно ставить отдельные приложения.', '2026-02-14 16:18:11', '2026-02-14 16:18:11', 1, 0, 0, NULL),
(17, 18, 1, 17, 5, 'Отличный городской квест: не слишком лёгкий, но и не перегруженный. Идеально для выходного.', '2026-02-16 15:26:47', '2026-02-16 15:26:47', 1, 0, 0, NULL),
(18, 19, 1, 18, 5, 'Обработка фото для галереи на сайте — приятный бонус, после квеста картинки смотрятся ещё лучше.', '2026-02-17 16:39:54', '2026-02-17 16:39:54', 1, 0, 0, NULL),
(19, 20, 1, 19, 5, 'Всё понравилось: маршрутизация, точки, тексты, голос. Чувствуется внимание к деталям.', '2026-02-18 15:44:36', '2026-02-18 15:44:36', 1, 0, 0, NULL),
(20, 21, 1, 20, 5, 'Один из лучших квест‑ботов, что я пробовал. Понятная логика, без багов, приятный дизайн.', '2026-02-20 16:11:23', '2026-02-20 16:11:23', 1, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `routes`
--

CREATE TABLE `routes` (
  `id` int UNSIGNED NOT NULL,
  `city_id` int UNSIGNED NOT NULL,
  `creator_id` int UNSIGNED DEFAULT NULL COMMENT 'ID модератора-создателя',
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
  `season` enum('winter','spring','summer','autumn','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'all' COMMENT 'Сезон',
  `is_published` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Опубликован ли маршрут',
  `commission_percent` decimal(5,2) DEFAULT NULL COMMENT 'Комиссия платформы для этого маршрута',
  `max_earnings` decimal(10,2) DEFAULT NULL COMMENT 'Лимит заработка с квеста (гроши), NULL = без лимита'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `routes`
--

INSERT INTO `routes` (`id`, `city_id`, `creator_id`, `name`, `name_en`, `description`, `description_en`, `route_type`, `price`, `estimated_duration`, `distance`, `is_active`, `order`, `max_hints_per_route`, `created_at`, `updated_at`, `difficulty`, `duration_minutes`, `age_min`, `age_max`, `season`, `is_published`, `commission_percent`, `max_earnings`) VALUES
(1, 1, NULL, 'Сердце столицы: Сквозь века', 'The Heart of the Capital: Through the Ages', '🏙 Квест-прогулка «Сердце Москвы»\r\n\r\nЧто это? Не скучная экскурсия, а городская игра 🕵️‍♂️. Исторический центр станет вашим полем для исследований. Забудьте про Википедию — все ответы спрятаны в архитектуре и деталях вокруг вас.\r\n👥 ДЛЯ КОГО?\r\n\r\n    👯‍♂️ Друзья и пары — для небанального отдыха.\r\n\r\n    🧑‍💼 Команды — легкий тимбилдинг (2–10 чел).\r\n\r\n    🏠 Местные и туристы — чтобы сказать: «Я был тут сто раз, но этого не видел!»\r\n\r\n    Специальных знаний не нужно. Только внимательность 👀 и азарт.\r\n\r\n📊 ЦИФРЫ\r\n\r\n    ⏱️ Время: ~2 часа.\r\n\r\n    👟 Дистанция: ~5 км (спокойный темп).\r\n\r\n    ☀️ Когда: Строго в светлое время суток (старт до 17:00). Ночью подсказок не видно!\r\n\r\n🧠 ЧТО БУДЕМ ДЕЛАТЬ?\r\n\r\n    🔍 Искать тайные знаки на фасадах.\r\n\r\n    🐉 Ловить мифических существ.\r\n\r\n    🧩 Решать загадки без Гугла.\r\n\r\n    📸 Делать фото в лучших локациях.\r\n\r\n🎒 С СОБОЙ\r\n\r\n    Удобная обувь (много брусчатки!).\r\n\r\n    Заряженный телефон 🔋.\r\n\r\n    Настрой на открытия.\r\n', '🏙 Quest walk “Heart of Moscow”\r\n\r\nWhat is this? Not a boring excursion, but a city game 🕵️‍♂️. The historical center will be your field of exploration. Forget Wikipedia - all the answers are hidden in the architecture and details around you.\r\n👥 FOR WHOM?\r\n\r\n    👯‍♂️ Friends and couples - for a non-trivial vacation.\r\n\r\n    🧑‍💼 Teams - easy team building (2–10 people).\r\n\r\n    🏠 Locals and tourists - to say: “I’ve been here a hundred times, but I haven’t seen this!”\r\n\r\n    No special knowledge required. Only attentiveness 👀 and excitement.\r\n\r\n📊 NUMBERS\r\n\r\n    ⏱️ Time: ~2 hours.\r\n\r\n    👟 Distance: ~5 km (calm pace).\r\n\r\n    ☀️ When: Strictly during daylight hours (start before 17:00). You can\'t see the clues at night!\r\n\r\n🧠 WHAT SHALL WE DO?\r\n\r\n    🔍 Look for secret signs on facades.\r\n\r\n    🐉 Catch mythical creatures.\r\n\r\n    🧩 Solve riddles without Google.\r\n\r\n    📸 Take photos in the best locations.\r\n\r\n🎒 WITH YOU\r\n\r\n    Comfortable shoes (lots of cobblestones!).\r\n\r\n    Charged phone 🔋.\r\n\r\n    The mood for discovery.', 'WALKING', 399, 130, NULL, 1, 0, 3, '2026-01-20 19:31:18', '2026-01-21 18:11:19', 2, 60, NULL, NULL, 'all', 1, NULL, NULL);

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
-- Структура таблицы `survey_results`
--

CREATE TABLE `survey_results` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `progress_id` int UNSIGNED NOT NULL,
  `route_id` int UNSIGNED NOT NULL,
  `answers` json NOT NULL,
  `reward_given` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'restart_notifications_enabled', '0', 'Уведомления о перезапуске бота (1 - включено, 0 - выключено)', '2026-01-18 12:21:30', '2026-01-18 12:22:47'),
(2, 'channel_stats_enabled', '1', 'Ежедневная отправка статистики канала админам в Telegram (1 - вкл, 0 - выкл)', NOW(), NOW()),
(3, 'channel_stats_time', '08:00', 'Время отправки статистики канала по Москве (ЧЧ:ММ)', NOW(), NOW());

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
  `is_bonus` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Бонусное задание (необязательное)',
  `bonus_reward` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Награда за бонусное задание (гроши)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `point_id`, `order`, `task_text`, `task_text_en`, `task_type`, `text_answer`, `text_answer_hint`, `accept_partial_match`, `max_attempts`, `is_bonus`, `bonus_reward`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'Задание №1\nТы у цели! Перед тобой список всех правителей династии Романовых — от Михаила Федоровича до Николая II.\nСпусти взгляд выше списка имен. Там изображен мифический зверь, который держит меч и щит. У него тело льва, а крылья орла.\n', 'Task No. 1 You are at the finish line! Before you is a list of all the rulers of the Romanov dynasty—from Mikhail Fyodorovich to Nicholas II. But look just below the list of names. Find the embossed coat of arms of the Romanov family. It depicts a mythical beast holding a sword and a shield. It has the body of a lion and the wings of an eagle.', 'text', 'ГРИФОН|GRIFFIN|GRIFON', NULL, 1, 3, 0, 0.00, '2026-01-20 22:34:30', '2026-01-20 22:34:30'),
(2, 2, 0, 'Задание №2\n\n👀 Посмотри на гранитную плиту над самим пламенем.\n\n🛡️ На ней лежат отлитые из бронзы символы воинской доблести: боевое знамя, лавровая ветвь и один главный элемент экипировки бойца.\n\n✍️ Напиши, какой предмет лежит на знамени?\n\n----------------------------------------\n\n💡 (Ответ из одного слова)', 'Task No. 2\n\n👀 Look at the granite slab above the flame itself.\n\n🛡️ On it are symbols of military valor cast in bronze: a battle banner, a laurel branch and one main element of a fighter’s equipment.\n\n✍️ Write what item is on the banner?\n\n----------------------------------------\n\n💡 (One word answer)', 'text', 'ШЛЕМ|КАСКА|HELMET|CAP', NULL, 1, 3, 0, 0.00, '2026-01-20 22:58:42', '2026-01-20 22:58:42'),
(3, 3, 0, '📸 Сделай крутое фото: Сфотографируйся на фоне памятника Маршалу Жукову и Исторического музея на память!', '📸 Take a cool photo: Take a photo in front of the monument to Marshal Zhukov and the Historical Museum as a souvenir!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 19:14:54', '2026-01-21 19:14:54'),
(4, 4, 0, 'Задание №4\n\n🔍 В центре знака находится круг, а вокруг него — квадрат с изображениями животных и растений, ориентированных по сторонам света.\n\n👀 Внимательно посмотри на четыре угла этого бронзового квадрата.\n\n🦉 В одном из них изображена мудрая лесная птица. Напиши название этой птицы.\n\n----------------------------------------\n\n💡 (Ответ из одного слова)', 'Task No. 4\n\n🔍 In the center of the sign there is a circle, and around it there is a square with images of animals and plants oriented to the cardinal points.\n\n👀 Take a close look at the four corners of this bronze square.\n\n🦉 One of them depicts a wise forest bird. Write the name of this bird.\n\n----------------------------------------\n\n💡 (One word answer)', 'text', 'СОВА|OWL', NULL, 1, 3, 0, 0.00, '2026-01-21 19:27:51', '2026-01-21 19:27:51'),
(5, 4, 1, '📸 ЗАДАНИЕ ДЛЯ КОМАНДЫ\n\n✨ Здесь принято загадывать желания! Встаньте в самый центр бронзового круга, спиной к воротам, и сделайте общее фото.\n\n🪙 По старой традиции, чтобы желание сбылось, нужно бросить монетку через левое плечо так, чтобы она осталась в пределах металлического знака.\n\n🍀 Загадывайте самое смелое желание — говорят, на Нулевом километре они сбываются быстрее!', '📸 TEAM TASK\n\n✨ It’s common to make wishes here! Stand in the very center of the bronze circle, with your back to the gate, and take a group photo.\n\n🪙 According to the old tradition, for a wish to come true, you need to throw a coin over your left shoulder so that it remains within the metal sign.\n\n🍀 Make your wildest wish - they say they come true faster at Kilometer Zero!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 19:23:20', '2026-01-21 19:23:20'),
(7, 6, 0, '📝 Задание:\n\nГУМ — это не просто магазин, а шедевр инженерной мысли XIX века.\n\nВнимательно посмотри на верхнюю часть центрального фасада. Там, среди декоративных элементов, высечены четыре цифры — год постройки этого здания.\n\n❓ Вопрос: Напиши этот год (строительство закончилось в 189...).\n\n✍️ Отправьте ответ текстом (4 цифры)!', '📝 Assignment:\n\nGUM is not just a store, but a masterpiece of 19th century engineering.\n\nLook carefully at the upper part of the central façade. There, among the decorative elements, four digits are carved — the year of construction of this building.\n\n❓ Question: Write this year (construction ended in 189...).\n\n✍️ Send your answer by text (4 digits)!', 'text', '1893', NULL, 1, 3, 0, 0.00, '2026-01-31 06:00:00', '2026-01-31 06:00:00'),
(8, 7, 0, '📸 ЗАДАНИЕ: ФОТО-ПАУЗА\n\n🛡️ Эти звери — настоящие стражи времени. С самого XVII века они охраняют вход в главную типографию страны.\n\n🤳 Сделай крупное фото Льва и Единорога на фасаде (или селфи на их фоне).\n\n🔍 Постарайся поймать такой ракурс, чтобы можно было разглядеть детали их схватки!', '📸 TASK: PHOTO-PAUSE\n\n🛡️ These animals are real guardians of time. Since the 17th century, they have been guarding the entrance to the country\'s main printing house.\n\n🤳 Take a close-up photo of the Lion and Unicorn on the façade (or a selfie against their background).\n\n🔍 Try to catch an angle so that you can see the details of their fight!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 19:50:46', '2026-01-21 19:50:46'),
(9, 8, 0, '📝 Задание:\n\nБратья Третьяковы прорубили этот путь прямо сквозь древнюю крепостную стену Китай-города для удобства покупателей и логистики товаров.\n\n❓ Вопрос: Как официально называется такой тип улицы (сквозной путь)?\n\n💡 Подсказка: Посмотри на синюю табличку с адресом на выходе из арки.\n\n✍️ Отправьте ответ текстом (одно слово)!', '📝 Assignment:\n\nThe Tretyakov brothers cut this path right through the ancient fortress wall of Kitay-Gorod for the convenience of buyers and logistics of goods.\n\n❓ Question: What is the official name of this type of street (through passage)?\n\n💡 Hint: Look at the blue sign with the address at the exit from the arch.\n\n✍️ Send your answer by text (one word)!', 'text', 'ПРОЕЗД|PASSAGE|PASSAGEWAY', NULL, 1, 3, 0, 0.00, '2026-01-31 06:00:00', '2026-01-31 06:00:00'),
(10, 9, 0, '🔎 Задание №9\n\n🧐 Твоя задача — определить, что это за место. Внимательно посмотри на фасад здания: его современное название огромными буквами написано прямо над входом.\n\n🔠 Чаще всего его сокращают до лаконичной аббревиатуры из трех букв.\n\n----------------------------------------\n\n✍️ Напиши эту аббревиатуру (3 буквы).', '🔎 Task No. 9\n\n🧐 Your task is to determine what kind of place this is. Take a close look at the façade of the building: its modern name is written in huge letters right above the entrance.\n\n🔠 Most often it is shortened to a laconic abbreviation of three letters.\n\n----------------------------------------\n\n✍️ Write this abbreviation (3 letters).', 'text', 'цдм|CDM|ЦДМ', NULL, 1, 3, 0, 0.00, '2026-01-21 19:59:18', '2026-01-21 19:59:18'),
(11, 10, 0, '🔎 Задание №10\n\n📜 Посмотри на информационную табличку на стене храма. Она хранит в себе историю этого места в цифрах.\n\n📅 На ней указаны два года, связанные с важными этапами строительства и жизни этого здания.\n\n❓ Вопрос: Какова разница в годах между этими двумя датами?\n\n----------------------------------------\n\n✍️ Ответ — одно число. (Просто вычти из большего года меньший).', '🔎 Task No. 10\n\n📜 Look at the information plaque on the wall of the temple. It contains the history of this place in numbers.\n\n📅 It indicates two years associated with important stages of the construction and life of this building.\n\n❓ Question: What is the difference in years between these two dates?\n\n----------------------------------------\n\n✍️ The answer is one number. (Simply subtract the smaller year from the larger one).', 'text', '12', NULL, 1, 3, 0, 0.00, '2026-01-21 20:03:24', '2026-01-21 20:03:24'),
(12, 11, 0, '🔎 Задание №11\n\n🏮 Эта буква «М» интересна не сама по себе. Если ты посмотришь на её основание, то увидишь две цветные горизонтальные полоски.\n\n🧩 Это не просто украшение, а важный шифр для пассажиров, по ним видно, какие ветки здесь пересекаются.\n\n✍️ Напиши оба цвета в одном слове слитно. Подойдёт и один цвет.', '🔎 Task No. 11\n\n🏮 This letter “M” is not interesting in itself. If you look at its base, you will see two colored horizontal stripes.\n\n🧩 This is not just a decoration, but an important code for passengers, they show which lines intersect here.\n\n✍️ Write both colors in one word. One color is also accepted.', 'text', 'ОранжевыйФиолетовый|ФиолетовыйОранжевый|OrangePurple|PurpleOrange', NULL, 1, 4, 0, 0.00, '2026-01-21 20:07:38', '2026-01-21 20:07:38'),
(13, 12, 0, '📸 ЗАДАНИЕ: ФОТО-ЧЕК\n\n⛓️ Чугунная мощь: Этот памятник выглядит суровым и тяжелым, ведь он полностью отлит из металла.\n\n🛡️ Рассмотри детали: Обойди его вокруг, изучи барельефы с изображениями русских крестьян и солдат — в них застыла история подвига.\n\n🤳 Сделай фото (или селфи) на фоне этой часовни.\n\n✨ Важное условие: Постарайся, чтобы в кадр попал золоченый православный крест на самой вершине шатра!', 'Here\'s your next block:\n📸 TASK: PHOTO CHECK\n\n⛓️ Cast Iron Power: This monument looks harsh and heavy, because it is completely cast from metal.\n\n🛡️ Look at the details: Walk around it, study the bas-reliefs with images of Russian peasants and soldiers - the story of the feat is frozen in them.\n\n🤳 Take a photo (or selfie) in front of this chapel.\n\n✨ Important condition: Try to get the gilded Orthodox cross at the very top of the tent into the frame!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 20:10:39', '2026-01-21 20:10:39'),
(14, 13, 0, '🔎 Задание №13\n\n    Там указано, кому он посвящен («Святым равноапостольным...»), и от кого он был установлен.\n\n🇷🇺 Найди слово, которое описывает Россию в этой торжественной фразе.\n\n❓ Вопрос: Какая именно Россия поставила этот памятник?\n\n----------------------------------------\n\n✍️ Ответ — одно слово (прилагательное).', '🔎 Task No. 13\n\n    It indicates to whom it is dedicated (“To the Saints Equal to the Apostles...”) and from whom it was established.\n\n🇷🇺 Find the word that describes Russia in this solemn phrase.\n\n❓ Question: Which Russia exactly erected this monument?\n\n----------------------------------------\n\n✍️ The answer is one word (adjective).', 'text', 'БЛАГОДАРНАЯ|GRATEFUL', NULL, 1, 3, 0, 0.00, '2026-01-21 20:13:13', '2026-01-21 20:13:13'),
(15, 14, 0, '🔎 ЗАДАНИЕ: УГОЛ ЗРЕНИЯ\n\n🗼 Московская «Пизанская башня»: Посмотри на колокольню храма очень внимательно, сравнивая её вертикальные линии с соседними зданиями. Ты стоишь прямо перед архитектурным феноменом!\n\n📐 Из-за особенностей грунта (тех самых болотистых «куличек») фундамент здания со временем просел, и колокольня приобрела свою знаменитую особенность.\n\n❓ Вопрос: Что не так с колокольней этого храма?\n\n----------------------------------------\n\n✍️ Опиши её состояние одним глаголом или кратким прилагательным.', '🔎 TASK: VIEW ANGLE\n\n🗼 Moscow “Leaning Tower of Pisa”: Look at the bell tower of the temple very carefully, comparing its vertical lines with neighboring buildings. You are standing right in front of an architectural phenomenon!\n\n📐 Due to the characteristics of the soil (those swampy “wraps”), the foundation of the building sank over time, and the bell tower acquired its famous feature.\n\n❓ Question: What\'s wrong with the bell tower of this temple?\n\n----------------------------------------\n\n✍️ Describe her condition with one verb or adjective.', 'text', 'наклонена|падает|leaning|tilted|leans|tilts', NULL, 1, 3, 0, 0.00, '2026-01-21 20:15:25', '2026-01-21 20:15:25'),
(16, 15, 0, '📸 Сделай фото панорамы с видом на Кремль и Москву-реку!', '📸 Take a photo of a panorama with a view of the Kremlin and the Moscow River!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 21:14:48', '2026-01-21 21:14:48'),
(17, 16, 0, '📝 Задание:\n\nПеред тобой большая лестница. Посчитай ступени!\n\n❓ Вопрос: Сколько больших ступеней на этой лестнице?\n\n✍️ Отправьте ответ текстом (число)!', '📝 Assignment:\n\nThere is a large staircase in front of you. Count the steps!\n\n❓ Question: How many big steps are there on this staircase?\n\n✍️ Send your answer by text (date)!', 'text', '41|forty-one', NULL, 1, 10, 0, 0.00, '2026-01-21 21:16:06', '2026-01-21 21:16:06'),
(18, 17, 0, '📝 Задание:\n\nС моста открывается один из лучших видов на город!\n\n📸 Сделайте общее командное селфи на мосту. В кадре обязательно должны быть:\n• Ваша команда\n• Москва-река прямо под вами\n• Панорама Кремля и собор Василия Блаженного\n\n📷 Отправьте фото в чат!\n', '📝 Assignment:\n\nThe bridge offers one of the best views of the city!\n\n📸 Take a team selfie on the bridge. The frame must include:\n• Your team\n• The Moscow River is right below you\n• Panorama of the Kremlin and St. Basil\'s Cathedral\n\n📷 Send a photo to the chat!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 21:38:39', '2026-01-21 21:38:39'),
(19, 18, 0, '📝 Задание:\n\n📸 Сделай фото на фоне исторического жёлтого здания!\n\n📷 Отправьте фото в чат!', '📝 Assignment:\n\n📸 Take a photo in front of the historical yellow building!\n\n📷 Send a photo to the chat!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 21:39:56', '2026-01-21 21:39:56'),
(20, 19, 0, '📝 Задание:\n\nВы это сделали! Перед вами — один из самых узнаваемых храмов мира.\n\nПрямо перед собором стоит первый в Москве скульптурный памятник. Он посвящён Кузьме Минину и князю Дмитрию Пожарскому, которые собрали народное ополчение и освободили город от захватчиков.\n\nРассмотри надпись на гранитном постаменте («Гражданину Минину и князю Пожарскому благодарная Россія...»).\n\n❓ Вопрос: В каком году был установлен этот памятник? На постаменте год указан старым стилем с буквами, но цифры читаются легко.', '📝 Assignment:\n\nYou did it! Before you is one of the most recognizable temples in the world.\n\nRight in front of the cathedral stands the first sculptural monument in Moscow. It is dedicated to Kuzma Minin and Prince Dmitry Pozharsky, who gathered the people\'s militia and liberated the city from the invaders.\n\nLook at the inscription on the granite pedestal (“To Citizen Minin and Prince Pozharsky, grateful Russia...”).\n\n❓ Question: In what year was this monument erected? On the pedestal the year is indicated in the old style with letters, but the numbers are easy to read.', 'text', '1818', NULL, 1, 10, 0, 0.00, '2026-01-21 21:41:44', '2026-01-21 21:41:44'),
(21, 19, 1, 'Сделайте финальное командное фото на фоне Собора Василия Блаженного!', 'Take your final team photo with St. Basil\'s Cathedral in the background!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-01-21 21:42:35', '2026-01-21 21:42:35'),
(22, 5, 0, '📝 Задание:\n\nЭтот собор — одна из самых ярких и фотогеничных точек маршрута.\n\n📸 Сделайте классное командное (или селфи) фото на фоне его фасада. Постарайтесь, чтобы в кадр попали и золотые купола, и нарядные белокаменные «кокошники» на крыше!\n\n📷 Отправьте фото в чат!\n', '📝 Assignment:\n\nThis cathedral is one of the most striking and photogenic points of the route.\n\n📸 Take a cool team (or selfie) photo with its façade in the background. Try to include both the golden domes and the elegant white stone “kokoshniks” on the roof!\n\n📷 Send a photo to the chat!', 'photo', NULL, NULL, 1, 3, 0, 0.00, '2026-02-01 16:05:19', '2026-02-01 16:05:19');

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
  `type` enum('deposit','purchase','transfer_out','transfer_in','refund','adjustment','referral_reward') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип транзакции',
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
  `show_map` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Показывать кнопку Яндекс.Карты в блоке «Как добраться»',
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('USER','MODERATOR','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USER',
  `is_banned` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `ban_until` timestamp NULL DEFAULT NULL COMMENT 'Заблокирован до (NULL = не заблокирован)',
  `ban_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Причина блокировки',
  `banned_by` int UNSIGNED DEFAULT NULL COMMENT 'ID админа который заблокировал',
  `banned_at` timestamp NULL DEFAULT NULL COMMENT 'Время блокировки',
  `referred_by_id` int UNSIGNED DEFAULT NULL,
  `is_profile_public` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Публичный профиль (1=да, 0=скрыт)',
  `referral_level` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Текущий реферальный уровень (0-4)',
  `paid_referrals_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Количество рефералов совершивших покупку',
  `referral_earnings` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Всего заработано с рефералов',
  `is_partner` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Статус официального партнёра'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `telegram_id`, `username`, `first_name`, `last_name`, `language`, `show_map`, `photo_url`, `role`, `is_banned`, `created_at`, `updated_at`, `last_login`, `ban_until`, `ban_reason`, `banned_by`, `banned_at`, `referred_by_id`, `is_profile_public`, `referral_level`, `paid_referrals_count`, `referral_earnings`, `is_partner`) VALUES
(1, 1644233050, 'LEGENDA_SD', '༺Leͥgeͣnͫda༻ᴳᵒᵈ', NULL, 'ru', 0, NULL, 'ADMIN', 0, '2026-01-04 22:41:08', '2026-01-18 16:34:51', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(2, 1139810664, 'an1k0nda', 'an1k0nda', NULL, 'ru', 0, NULL, 'ADMIN', 0, '2026-01-06 10:14:32', '2026-01-12 19:47:07', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(3, 2000000003, 'Exsydener', 'Exsydener', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(4, 2000000004, 'cdcd3113', 'cdcd3113', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(5, 2000000005, 'Depozit45', 'Depozit45', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(6, 2000000006, 'geshtaltman53', 'geshtaltman53', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(7, 2000000007, 'Ivan5516', 'Ivan5516', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(8, 2000000008, 'az12345658', 'az12345658', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(9, 2000000009, 'slaughter_man', 'slaughter_man', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(10, 2000000010, 'WhyIzik', 'WhyIzik', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(11, 2000000011, 'vikulyababyyy', 'vikulyababyyy', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(12, 2000000012, 'nktevg', 'nktevg', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(13, 2000000013, 'pupa_flex', 'pupa_flex', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(14, 2000000014, 'forsyq', 'forsyq', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(15, 2000000015, 'sidorov_artem94', 'sidorov_artem94', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(16, 2000000016, 'koggda', 'koggda', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(17, 2000000017, 'AGR_42', 'AGR_42', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(18, 2000000018, 'Hugo_Vlad0', 'Hugo_Vlad0', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(19, 2000000019, 'Olgarossia77', 'Olgarossia77', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(20, 2000000020, 'kantiksk', 'kantiksk', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(21, 2000000021, 'ShiZobazis0_0', 'ShiZobazis0_0', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-28 00:00:00', '2026-01-28 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0),
(22, 7886808180, 'FitoDomik', '🤴', NULL, 'ru', 0, NULL, 'USER', 0, '2026-01-31 05:38:52', '2026-01-31 05:38:52', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0);

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
  `moderation_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Статус модерации',
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
  `status` enum('IN_PROGRESS','COMPLETED','ABANDONED','PAUSED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN_PROGRESS',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL COMMENT 'Время постановки на паузу',
  `total_paused_seconds` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Общее время на паузе (секунды)',
  `total_earned` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Сколько заработал на этом прохождении',
  `is_paused` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Квест на паузе',
  `points_completed` int UNSIGNED NOT NULL DEFAULT '0',
  `photo_hashes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON хешей (антифрод)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `route_id`, `current_point_id`, `current_point_order`, `status`, `started_at`, `completed_at`, `paused_at`, `total_paused_seconds`, `total_earned`, `is_paused`, `points_completed`, `photo_hashes`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 19, 19, 'COMPLETED', '2026-01-22 07:00:00', '2026-01-22 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-22 07:00:00', '2026-01-22 09:00:00'),
(2, 3, 1, 19, 19, 'COMPLETED', '2026-01-22 08:00:00', '2026-01-22 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-22 08:00:00', '2026-01-22 10:00:00'),
(3, 4, 1, 19, 19, 'COMPLETED', '2026-01-22 11:00:00', '2026-01-22 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-22 11:00:00', '2026-01-22 13:00:00'),
(4, 5, 1, 19, 19, 'COMPLETED', '2026-01-23 07:00:00', '2026-01-23 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-23 07:00:00', '2026-01-23 09:00:00'),
(5, 6, 1, 19, 19, 'COMPLETED', '2026-01-23 08:00:00', '2026-01-23 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-23 08:00:00', '2026-01-23 10:00:00'),
(6, 7, 1, 19, 19, 'COMPLETED', '2026-01-23 11:00:00', '2026-01-23 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-23 11:00:00', '2026-01-23 13:00:00'),
(7, 8, 1, 19, 19, 'COMPLETED', '2026-01-24 07:00:00', '2026-01-24 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-24 07:00:00', '2026-01-24 09:00:00'),
(8, 9, 1, 19, 19, 'COMPLETED', '2026-01-24 08:00:00', '2026-01-24 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-24 08:00:00', '2026-01-24 10:00:00'),
(9, 10, 1, 19, 19, 'COMPLETED', '2026-01-24 11:00:00', '2026-01-24 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-24 11:00:00', '2026-01-24 13:00:00'),
(10, 11, 1, 19, 19, 'COMPLETED', '2026-01-25 07:00:00', '2026-01-25 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-25 07:00:00', '2026-01-25 09:00:00'),
(11, 12, 1, 19, 19, 'COMPLETED', '2026-01-25 08:00:00', '2026-01-25 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-25 08:00:00', '2026-01-25 10:00:00'),
(12, 13, 1, 19, 19, 'COMPLETED', '2026-01-25 11:00:00', '2026-01-25 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-25 11:00:00', '2026-01-25 13:00:00'),
(13, 14, 1, 19, 19, 'COMPLETED', '2026-01-26 07:00:00', '2026-01-26 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-26 07:00:00', '2026-01-26 09:00:00'),
(14, 15, 1, 19, 19, 'COMPLETED', '2026-01-26 08:00:00', '2026-01-26 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-26 08:00:00', '2026-01-26 10:00:00'),
(15, 16, 1, 19, 19, 'COMPLETED', '2026-01-26 11:00:00', '2026-01-26 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-26 11:00:00', '2026-01-26 13:00:00'),
(16, 17, 1, 19, 19, 'COMPLETED', '2026-01-19 07:00:00', '2026-01-19 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-19 07:00:00', '2026-01-19 09:00:00'),
(17, 18, 1, 19, 19, 'COMPLETED', '2026-01-19 08:00:00', '2026-01-19 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-19 08:00:00', '2026-01-19 10:00:00'),
(18, 19, 1, 19, 19, 'COMPLETED', '2026-01-19 11:00:00', '2026-01-19 13:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-19 11:00:00', '2026-01-19 13:00:00'),
(19, 20, 1, 19, 19, 'COMPLETED', '2026-01-28 07:00:00', '2026-01-28 09:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-28 07:00:00', '2026-01-28 09:00:00'),
(20, 21, 1, 19, 19, 'COMPLETED', '2026-01-28 08:00:00', '2026-01-28 10:00:00', NULL, 0, 0.00, 0, 19, NULL, '2026-01-28 08:00:00', '2026-01-28 10:00:00');

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
-- Дамп данных таблицы `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `telegram_id`, `token`, `is_used`, `created_at`, `expires_at`, `used_at`) VALUES
(1, 1644233050, '4ff818787a94f8985955f2db48de6e018b5c6d99535a55c55739d62667440116', 1, '2026-02-01 10:02:51', '2026-02-01 10:07:51', '2026-02-01 13:02:53'),
(2, 1644233050, '6599e3666b7dfd7d5d8def92986a1b44effe1f9f53d7c94277f3e07899adb81f', 1, '2026-02-07 02:40:47', '2026-02-07 02:45:47', '2026-02-07 05:40:49'),
(3, 1644233050, 'a75d0a3323916005099c50f645bae62709b2ba4b4e95f0661459ad6884861a90', 1, '2026-02-07 02:41:03', '2026-02-07 02:46:03', '2026-02-07 05:41:04');

-- --------------------------------------------------------

--
-- Структура таблицы `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'ID модератора',
  `amount` decimal(15,2) NOT NULL COMMENT 'Сумма вывода',
  `payment_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Реквизиты для вывода',
  `status` enum('pending','processing','completed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `processed_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Запросы на вывод средств';

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
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_cities_creator` (`creator_id`);

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
-- Индексы таблицы `moderator_balances`
--
ALTER TABLE `moderator_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_moderator_balances_user` (`user_id`);

--
-- Индексы таблицы `moderator_requests`
--
ALTER TABLE `moderator_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_moderator_requests_user` (`user_id`),
  ADD KEY `idx_moderator_requests_status` (`status`),
  ADD KEY `fk_moderator_requests_reviewer` (`reviewed_by`);

--
-- Индексы таблицы `moderator_transactions`
--
ALTER TABLE `moderator_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_moderator_transactions_user` (`user_id`),
  ADD KEY `idx_moderator_transactions_route` (`route_id`),
  ADD KEY `idx_moderator_transactions_type` (`type`),
  ADD KEY `fk_moderator_transactions_buyer` (`buyer_user_id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payments_user_route` (`user_id`,`route_id`);

--
-- Индексы таблицы `platform_earnings`
--
ALTER TABLE `platform_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_platform_earnings_route` (`route_id`),
  ADD KEY `idx_platform_earnings_moderator` (`moderator_id`),
  ADD KEY `idx_platform_earnings_buyer` (`buyer_user_id`);

--
-- Индексы таблицы `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_platform_settings_key` (`key`);

--
-- Индексы таблицы `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_route_id` (`route_id`),
  ADD KEY `idx_points_audio_enabled` (`audio_enabled`),
  ADD KEY `idx_points_audio_language` (`audio_language`),
  ADD KEY `idx_points_route_order` (`route_id`,`order`);

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
-- Индексы таблицы `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_route` (`route_id`);

--
-- Индексы таблицы `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quiz_progress` (`progress_id`),
  ADD KEY `idx_quiz_user` (`user_id`);

--
-- Индексы таблицы `reference_images`
--
ALTER TABLE `reference_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_point_id` (`point_id`);

--
-- Индексы таблицы `referral_levels`
--
ALTER TABLE `referral_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_level` (`level`);

--
-- Индексы таблицы `referral_rewards`
--
ALTER TABLE `referral_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_referral_id` (`referral_id`);

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
  ADD KEY `reviews_ibfk_3` (`progress_id`),
  ADD KEY `idx_reviews_route_approved` (`route_id`,`is_approved`);

--
-- Индексы таблицы `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_city_id` (`city_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_routes_difficulty` (`difficulty`),
  ADD KEY `idx_routes_duration` (`duration_minutes`),
  ADD KEY `idx_routes_season` (`season`),
  ADD KEY `idx_routes_creator` (`creator_id`);

--
-- Индексы таблицы `route_tags`
--
ALTER TABLE `route_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_route_tag` (`route_id`,`tag_id`),
  ADD KEY `idx_route_id` (`route_id`),
  ADD KEY `idx_tag_id` (`tag_id`);

--
-- Индексы таблицы `survey_results`
--
ALTER TABLE `survey_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_survey_progress` (`progress_id`),
  ADD KEY `idx_survey_user` (`user_id`);

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
  ADD KEY `idx_users_banned_by` (`banned_by`),
  ADD KEY `fk_users_referred_by` (`referred_by_id`);

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
  ADD KEY `idx_user_progress_completed` (`route_id`,`status`,`completed_at`),
  ADD KEY `idx_user_progress_user_route_status` (`user_id`,`route_id`,`status`);

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
-- Индексы таблицы `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdrawal_requests_user` (`user_id`),
  ADD KEY `idx_withdrawal_requests_status` (`status`),
  ADD KEY `fk_withdrawal_requests_processor` (`processed_by`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- AUTO_INCREMENT для таблицы `moderator_balances`
--
ALTER TABLE `moderator_balances`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `moderator_requests`
--
ALTER TABLE `moderator_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `moderator_transactions`
--
ALTER TABLE `moderator_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `platform_earnings`
--
ALTER TABLE `platform_earnings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- AUTO_INCREMENT для таблицы `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `reference_images`
--
ALTER TABLE `reference_images`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `referral_levels`
--
ALTER TABLE `referral_levels`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `referral_rewards`
--
ALTER TABLE `referral_rewards`
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
-- AUTO_INCREMENT для таблицы `survey_results`
--
ALTER TABLE `survey_results`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
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
-- Ограничения внешнего ключа таблицы `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_cities_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `hints`
--
ALTER TABLE `hints`
  ADD CONSTRAINT `hints_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `moderator_balances`
--
ALTER TABLE `moderator_balances`
  ADD CONSTRAINT `fk_moderator_balances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `moderator_requests`
--
ALTER TABLE `moderator_requests`
  ADD CONSTRAINT `fk_moderator_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_moderator_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `moderator_transactions`
--
ALTER TABLE `moderator_transactions`
  ADD CONSTRAINT `fk_moderator_transactions_buyer` FOREIGN KEY (`buyer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_moderator_transactions_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_moderator_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `platform_earnings`
--
ALTER TABLE `platform_earnings`
  ADD CONSTRAINT `fk_platform_earnings_buyer` FOREIGN KEY (`buyer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_platform_earnings_moderator` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_platform_earnings_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE;

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
-- Ограничения внешнего ключа таблицы `referral_rewards`
--
ALTER TABLE `referral_rewards`
  ADD CONSTRAINT `fk_reward_referral` FOREIGN KEY (`referral_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reward_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_routes_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
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
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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

--
-- Ограничения внешнего ключа таблицы `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `fk_withdrawal_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_withdrawal_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
