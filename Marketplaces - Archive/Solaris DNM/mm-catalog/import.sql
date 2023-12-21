SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `catalog` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `catalog`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table categories
# ------------------------------------------------------------

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;

INSERT INTO `categories` (`id`, `parent_id`, `title`, `priority`)
VALUES
	(1,NULL,'Стимуляторы',10000),
	(2,1,'Кокаин',9900),
	(3,1,'Амфетамин',9890),
	(4,1,'Метамфетамин',9880),
	(5,1,'Разное',9870),
	(6,NULL,'Марихуана',9500),
	(7,6,'Шишки',9400),
	(8,6,'Гашиш',9390),
	(9,6,'План',9380),
	(10,6,'Разное',9370),
	(11,NULL,'Психоделики',9000),
	(12,11,'ЛСД',8900),
	(13,11,'Грибы',8890),
	(14,11,'ДО*',8880),
	(15,11,'*-NBOMe',8870),
	(16,11,'2C-*',8860),
	(17,11,'Мескалин',8850),
	(18,11,'Разное',8840),
	(19,NULL,'Эйфоретики',8500),
	(20,19,'МДМА',8400),
	(21,19,'Таблетки',8390),
	(22,19,'Мефедрон',8380),
	(23,19,'МДА',8370),
	(24,19,'Метилон (bk-MDMA)',8360),
	(25,19,'Разное',8350),
	(26,NULL,'Аптека',8000),
	(27,26,'Транквилизаторы',7900),
	(28,26,'Депрессанты',7890),
	(29,26,'Разное',7880),
	(30,NULL,'Диссоциативы',7500),
	(31,30,'Кетамин',7400),
	(32,30,'Метоксетамин (MXE)',7390),
	(33,30,'Разное',7380),
	(34,NULL,'Опиаты',7000),
	(35,34,'Героин',6900),
	(36,34,'Метадон',6890),
	(37,34,'Трамадол',6880),
	(38,34,'Фентанил',6870),
	(39,34,'Разное',6860),
	(40,NULL,'Наборы',6500),
	(41,40,'Для большой компании',6400),
	(42,40,'Для двоих',6390),
	(43,40,'В космос',6380),
	(44,40,'Разное',6370);

/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table cities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cities`;

CREATE TABLE `cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`id`),
  KEY `cities_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;

INSERT INTO `cities` (`id`, `title`, `priority`)
VALUES
	(1,'Москва',10000),
	(2,'Московская область',9950),
	(3,'Санкт-Петербург',9900),
	(4,'Отправка по России',9850),
	(5,'Отправка по Украине',9800),
	(6,'Отправка по России и СНГ',9750),
	(7,'Без региона',9700),
	(8,'Адлер',8000),
	(9,'Анапа',7950),
	(10,'Архангельск',7900),
	(11,'Астрахань',7850),
	(12,'Барнаул',7800),
	(13,'Белгород',7750),
	(14,'Бийск',7700),
	(15,'Благовещенск',7650),
	(16,'Боровск (Калужская область)',7600),
	(17,'Брянск',7550),
	(18,'Великий Новгород',7500),
	(19,'Владивосток',7450),
	(20,'Владимир',7400),
	(21,'Волгоград',7350),
	(22,'Вологда',7300),
	(23,'Воронеж',7250),
	(24,'Вышний Волочек',7200),
	(25,'Геленджик',7150),
	(26,'Дзержинск (Нижегородская обл.)',7100),
	(27,'Днепропетровск',7050),
	(28,'Евпатория',7000),
	(29,'Екатеринбург',6950),
	(30,'Запорожье',6900),
	(31,'Зеленоград',6850),
	(33,'Иваново',6750),
	(34,'Ижевск',6700),
	(35,'Иркутск',6650),
	(36,'Йошкар-Ола',6600),
	(37,'Казань',6550),
	(38,'Калуга',6500),
	(39,'Кемерово',6450),
	(40,'Керчь',6400),
	(41,'Киев',6350),
	(42,'Киров',6300),
	(43,'Клин',6250),
	(44,'Коктебель',6200),
	(45,'Коломна',6150),
	(46,'Кострома',6100),
	(47,'Краснодар',6050),
	(48,'Красноярск',6000),
	(49,'Крым',5950),
	(50,'Кстово (Нижегородская обл.)',5900),
	(51,'Курган',5850),
	(52,'Курск',5800),
	(53,'Липецк',5750),
	(54,'Львов',5700),
	(55,'Люберцы МО',5650),
	(56,'Магнитогорск',5600),
	(57,'Миасс',5550),
	(58,'Минск',5500),
	(59,'Набережные Челны',5450),
	(60,'Наро-Фоминск',5400),
	(61,'Нижневартовск',5350),
	(62,'Нижний Новгород',5300),
	(63,'Новокузнецк',5250),
	(64,'Новомосковск',5200),
	(65,'Новороссийск',5150),
	(66,'Новосибирск',5100),
	(67,'Обнинск',5050),
	(68,'Одесса',5000),
	(69,'Омск',4950),
	(70,'Орел',4900),
	(71,'Оренбург',4850),
	(72,'Пенза',4800),
	(73,'Первоуральск',4750),
	(74,'Пермь',4700),
	(75,'Петрозаводск',4650),
	(76,'Псков',4600),
	(77,'Ростов-на-Дону',4550),
	(78,'Рязань',4500),
	(79,'Самара',4450),
	(80,'Саратов',4400),
	(81,'Севастополь',4350),
	(83,'Серпухов',4250),
	(84,'Симферополь',4200),
	(85,'Смоленск',4150),
	(86,'Сочи',4100),
	(87,'Ставрополь',4050),
	(88,'Стерлитамак',4000),
	(89,'Судак',3950),
	(90,'Сургут',3900),
	(91,'Сухиничи',3850),
	(92,'Тамбов',3800),
	(93,'Тверь',3750),
	(94,'Тобольск',3700),
	(95,'Тольятти',3650),
	(96,'Томск',3600),
	(97,'Торжок',3550),
	(98,'Тула',3500),
	(99,'Тюмень',3450),
	(100,'Ульяновск',3400),
	(101,'Уфа',3350),
	(102,'Феодосия',3300),
	(103,'Хабаровск',3250),
	(104,'Харьков',3200),
	(105,'Чебоксары',3150),
	(106,'Челябинск',3100),
	(107,'Череповец',3050),
	(108,'Щёкино',3000),
	(109,'Якутск',2950),
	(110,'Ялта',2900),
	(111,'Ярославль',2850);

/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table failed_jobs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table fetched_images
# ------------------------------------------------------------

DROP TABLE IF EXISTS `fetched_images`;

CREATE TABLE `fetched_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remote_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fetched_images_app_id_index` (`app_id`),
  KEY `fetched_images_remote_url_index` (`remote_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table goods
# ------------------------------------------------------------

DROP TABLE IF EXISTS `goods`;

CREATE TABLE `goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_good_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url_local` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_cached` tinyint(1) NOT NULL DEFAULT '0',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_quests` tinyint(1) NOT NULL,
  `has_ready_quests` tinyint(1) NOT NULL,
  `buy_count` int(11) NOT NULL,
  `reviews_count` int(11) NOT NULL,
  `rating` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_app_id_index` (`app_id`),
  KEY `goods_city_id_index` (`city_id`),
  KEY `goods_city_id_category_id_index` (`city_id`,`category_id`),
  KEY `goods_app_id_app_good_id_index` (`app_id`,`app_good_id`),
  KEY `goods_app_id_has_quests_index` (`app_id`,`has_quests`),
  KEY `goods_city_id_has_quests_index` (`city_id`,`has_quests`),
  KEY `goods_city_id_category_id_has_quests_index` (`city_id`,`category_id`,`has_quests`),
  KEY `goods_app_id_has_quests_has_ready_quests_index` (`app_id`,`has_quests`,`has_ready_quests`),
  KEY `goods_city_id_has_ready_quests_index` (`city_id`,`has_ready_quests`),
  KEY `goods_city_id_category_id_has_ready_quests_index` (`city_id`,`category_id`,`has_ready_quests`),
  KEY `goods_has_quests_index` (`has_quests`),
  KEY `goods_has_quests_has_ready_quests_index` (`has_quests`,`has_ready_quests`),
  KEY `goods_city_id_has_quests_has_ready_quests_index` (`city_id`,`has_quests`,`has_ready_quests`),
  KEY `goods_category_id_has_quests_has_ready_quests_index` (`category_id`,`has_quests`,`has_ready_quests`),
  KEY `goods_city_id_category_id_has_quests_has_ready_quests_index` (`city_id`,`category_id`,`has_quests`,`has_ready_quests`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table goods_packages
# ------------------------------------------------------------

DROP TABLE IF EXISTS `goods_packages`;

CREATE TABLE `goods_packages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_good_id` int(11) NOT NULL,
  `app_package_id` int(11) NOT NULL,
  `amount` double(16,8) NOT NULL,
  `measure` enum('gr','piece','ml') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double(16,8) NOT NULL,
  `currency` enum('btc','rub','usd') COLLATE utf8mb4_unicode_ci NOT NULL,
  `preorder` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_packages_app_id_index` (`app_id`),
  KEY `goods_packages_good_id_index` (`good_id`),
  KEY `goods_packages_app_id_app_good_id_index` (`app_id`,`app_good_id`),
  KEY `goods_packages_app_id_good_id_index` (`app_id`,`good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table goods_positions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `goods_positions`;

CREATE TABLE `goods_positions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_package_id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `app_custom_place_id` int(11) DEFAULT NULL,
  `app_custom_place_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_positions_app_id_index` (`app_id`),
  KEY `goods_positions_good_id_index` (`good_id`),
  KEY `goods_positions_package_id_index` (`package_id`),
  KEY `goods_positions_good_id_package_id_index` (`good_id`,`package_id`),
  KEY `search` (`package_id`,`region_id`,`app_custom_place_id`,`app_custom_place_title`),
  KEY `goods_positions_app_id_package_id_index` (`app_id`,`package_id`),
  KEY `goods_positions_app_id_app_package_id_index` (`app_id`,`app_package_id`),
  KEY `goods_positions_app_id_region_id_index` (`app_id`,`region_id`),
  KEY `goods_positions_app_id_package_id_region_id_index` (`app_id`,`package_id`,`region_id`),
  KEY `goods_positions_app_id_app_package_id_region_id_index` (`app_id`,`app_package_id`,`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table jobs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `jobs`;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_index` (`queue`,`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table news
# ------------------------------------------------------------

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;

INSERT INTO `news` (`id`, `title`, `text`, `author`, `created_at`, `updated_at`)
VALUES
	(1,'Добро пожаловать в каталог Solaris!','<p>Приветствуем всех пользователей, читающих данную новость. Мы, Zanzi и команда, с удовольствием представляем вам долгожданное обновление - первую версию общего каталога магазинов на платформе Solaris. Номер версии говорит о том, что это наш первый шаг к созданию по-настоящему мощного и многофункционального продукта. Пройдя огромный путь и тесно сотрудничая с крупнейшими продавцами, мы создали лучший инструмент для ведения бизнеса. Наши главные принципы: честность, надежность, взаимоуважение и постоянное желание двигаться вперед.</p>\n\n<p>Последние события в мире даркнета полностью изменили правила игры, а существовавший долгое время оплот стабильности исчез, породив волну хаоса в теневом сегменте интернета. Рынок, который мы знали и любили, уходит в прошлое, открывая новые пути развития. Главное свойство любой социальной общности - умение адаптироваться. Именно способности к адаптации и приспособлению являются двигателеми эволюции - естественного процесса, непрерывно происходящего в любой среде. Поэтому проект Solaris ставит перед собой очень важную цель: мы хотим стать новым витком эволюции, который выведет рынок даркнета на новый уровень. Приглашаем всех пользователей принять участие в формировании нашего совместного будущего.</p>\n\n<p>В добрый путь!</p>','solaris','2017-09-04 05:32:59','2017-09-04 05:33:03');

/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table orders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `review_id` int(11) DEFAULT NULL,
  `good_id` int(11) DEFAULT NULL,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_order_id` int(11) NOT NULL,
  `app_good_id` int(11) NOT NULL,
  `good_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `good_image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `good_image_url_local` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `good_image_cached` tinyint(1) NOT NULL DEFAULT '0',
  `package_amount` double(16,8) NOT NULL,
  `package_measure` enum('gr','piece','ml') COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_price` double(16,8) NOT NULL,
  `package_currency` enum('btc','rub','usd') COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_preorder` tinyint(1) NOT NULL,
  `package_preorder_time` enum('24','48','72') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('preorder_paid','paid','problem','finished') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci,
  `app_created_at` timestamp NULL DEFAULT NULL,
  `app_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_app_id_index` (`app_id`),
  KEY `orders_app_id_user_id_index` (`app_id`,`user_id`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_user_id_good_image_cached_index` (`user_id`,`good_image_cached`),
  KEY `orders_created_at_index` (`created_at`),
  KEY `orders_app_created_at_index` (`app_created_at`),
  KEY `orders_user_id_good_image_cached_app_created_at_index` (`user_id`,`good_image_cached`,`app_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table orders_positions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `orders_positions`;

CREATE TABLE `orders_positions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `quest` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_positions_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table orders_reviews
# ------------------------------------------------------------

DROP TABLE IF EXISTS `orders_reviews`;

CREATE TABLE `orders_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_good_id` int(11) NOT NULL,
  `app_order_id` int(11) NOT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_rating` int(11) NOT NULL,
  `dropman_rating` int(11) NOT NULL,
  `item_rating` int(11) NOT NULL,
  `reply_text` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_reviews_app_id_index` (`app_id`),
  KEY `orders_reviews_good_id_index` (`good_id`),
  KEY `orders_reviews_app_id_good_id_index` (`app_id`,`good_id`),
  KEY `orders_reviews_app_good_id_index` (`app_good_id`),
  KEY `orders_reviews_app_id_app_good_id_index` (`app_id`,`app_good_id`),
  KEY `orders_reviews_order_id_index` (`order_id`),
  KEY `orders_reviews_app_id_order_id_index` (`app_id`,`order_id`),
  KEY `orders_reviews_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table password_resets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table regions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `regions`;

CREATE TABLE `regions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`id`),
  KEY `regions_city_id_index` (`city_id`),
  KEY `regions_parent_id_index` (`parent_id`),
  KEY `regions_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;

INSERT INTO `regions` (`id`, `city_id`, `parent_id`, `title`, `priority`)
VALUES
	(1,1,NULL,'ЦАО',1000),
	(2,1,NULL,'ВАО',990),
	(3,1,NULL,'САО',980),
	(4,1,NULL,'СВАО',970),
	(5,1,NULL,'ЮВАО',960),
	(6,1,NULL,'ЮАО',950),
	(7,1,NULL,'ЮЗАО',940),
	(8,1,NULL,'ЗАО',930),
	(9,1,NULL,'СЗАО',920),
	(10,1,NULL,'НАО',910),
	(11,1,NULL,'ТАО',900),
	(12,1,NULL,'Зеленоград',890),
	(13,3,NULL,'Адмиралтейский район',1000),
	(14,3,NULL,'Василеостровский район',990),
	(15,3,NULL,'Выборгский район',980),
	(16,3,NULL,'Калининский район',970),
	(17,3,NULL,'Кировский район',960),
	(18,3,NULL,'Колпинский район',950),
	(19,3,NULL,'Красногвардейский район',940),
	(20,3,NULL,'Красносельский район',930),
	(21,3,NULL,'Кронштадский район',920),
	(22,3,NULL,'Курортный район',910),
	(23,3,NULL,'Московский район',900),
	(24,3,NULL,'Невский район',890),
	(25,3,NULL,'Петроградский район',880),
	(26,3,NULL,'Петродворцовый район',870),
	(27,3,NULL,'Приморский район',860),
	(28,3,NULL,'Пушкинский район',850),
	(29,3,NULL,'Фрунзенский район',840),
	(30,3,NULL,'Центральный район',830);

/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table shops
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shops`;

CREATE TABLE `shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url_local` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_cached` tinyint(1) NOT NULL DEFAULT '0',
  `users_count` int(11) NOT NULL,
  `orders_count` int(11) NOT NULL,
  `rating` double(16,8) NOT NULL DEFAULT '0.00000000',
  `bitcoin_connections` int(11) NOT NULL,
  `bitcoin_block_count` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `eos_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_sync_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shops_app_id_index` (`app_id`),
  KEY `shops_app_key_index` (`app_key`),
  KEY `shops_last_sync_at_index` (`last_sync_at`),
  KEY `shops_enabled_index` (`enabled`),
  KEY `shops_enabled_last_sync_at_index` (`enabled`,`last_sync_at`),
  KEY `shops_image_cached_index` (`image_cached`),
  KEY `shops_image_cached_enabled_index` (`image_cached`,`enabled`),
  KEY `shops_image_cached_enabled_last_sync_at_index` (`image_cached`,`enabled`,`last_sync_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `totp_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacts_other` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacts_jabber` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacts_telegram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `buy_count` int(11) NOT NULL DEFAULT '0',
  `buy_sum` double(16,8) NOT NULL DEFAULT '0.00000000',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `news_last_read` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
