# Dump of table categories
# ------------------------------------------------------------

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
	(15,11,'Нбомы',8870),
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


# Dump of table custom_places
# ------------------------------------------------------------

CREATE TABLE `custom_places` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `good_id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_places_shop_id_index` (`shop_id`),
  KEY `custom_places_good_id_index` (`good_id`),
  KEY `custom_places_shop_id_good_id_index` (`shop_id`,`good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table employees
# ------------------------------------------------------------

CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `city_id` int(11) DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `role` enum('owner','support','deaddrop') COLLATE utf8_unicode_ci NOT NULL,
  `balance` double(16,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `goods_create` tinyint(1) NOT NULL DEFAULT '0',
  `goods_edit` tinyint(1) NOT NULL DEFAULT '0',
  `goods_delete` tinyint(1) NOT NULL DEFAULT '0',
  `goods_only_own_city` tinyint(1) NOT NULL DEFAULT '0',
  `quests_create` tinyint(1) NOT NULL DEFAULT '0',
  `quests_edit` tinyint(1) NOT NULL DEFAULT '0',
  `quests_delete` tinyint(1) NOT NULL DEFAULT '0',
  `quests_only_own_city` tinyint(1) NOT NULL DEFAULT '0',
  `quests_allowed_goods` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `sections_messages` tinyint(1) NOT NULL DEFAULT '0',
  `sections_orders` tinyint(1) NOT NULL DEFAULT '0',
  `sections_paid_services` tinyint(1) NOT NULL DEFAULT '0',
  `sections_finances` tinyint(1) NOT NULL DEFAULT '0',
  `sections_settings` tinyint(1) NOT NULL DEFAULT '0',
  `sections_pages` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_user_id_unique` (`user_id`),
  KEY `employees_shop_id_index` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table employees_earnings
# ------------------------------------------------------------

CREATE TABLE `employees_earnings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` double(16,8) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_earnings_shop_id_index` (`shop_id`),
  KEY `employees_earnings_shop_id_employee_id_index` (`shop_id`,`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table employees_logs
# ------------------------------------------------------------

CREATE TABLE `employees_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `good_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `action` enum('goods_add','goods_edit','goods_delete','packages_add','packages_edit','packages_delete','quests_add','quests_edit','quests_delete','orders_preorder','finance_payout','settings_page_add','settings_page_edit','settings_page_delete') COLLATE utf8_unicode_ci NOT NULL,
  `data` mediumtext COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_logs_shop_id_index` (`shop_id`),
  KEY `employees_logs_shop_id_employee_id_index` (`shop_id`,`employee_id`),
  KEY `employees_logs_shop_id_employee_id_action_index` (`shop_id`,`employee_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table employees_payouts
# ------------------------------------------------------------

CREATE TABLE `employees_payouts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sender_employee_id` int(11) NOT NULL,
  `operation_id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_payouts_shop_id_index` (`shop_id`),
  KEY `employees_payouts_shop_id_employee_id_index` (`shop_id`,`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table failed_jobs
# ------------------------------------------------------------

CREATE TABLE `failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8_unicode_ci NOT NULL,
  `queue` text COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods
# ------------------------------------------------------------

CREATE TABLE `goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `has_quests` tinyint(1) NOT NULL DEFAULT '0',
  `has_ready_quests` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_shop_id_index` (`shop_id`),
  KEY `goods_city_id_index` (`city_id`),
  KEY `goods_category_id_index` (`category_id`),
  KEY `goods_has_ready_quests_index` (`has_ready_quests`),
  KEY `search_whole` (`has_quests`),
  KEY `search_city` (`city_id`,`has_quests`),
  KEY `search_ready` (`has_ready_quests`,`has_quests`),
  KEY `search_city_ready` (`city_id`,`has_ready_quests`,`has_quests`),
  KEY `search_cat_whole` (`category_id`,`has_quests`),
  KEY `search_cat_city` (`category_id`,`city_id`,`has_quests`),
  KEY `search_cat_ready` (`category_id`,`has_ready_quests`,`has_quests`),
  KEY `search_cat_city_ready` (`category_id`,`city_id`,`has_ready_quests`,`has_quests`),
  KEY `shop_whole` (`shop_id`,`has_quests`),
  KEY `shop_city` (`shop_id`,`city_id`,`has_quests`),
  KEY `shop_ready` (`shop_id`,`has_ready_quests`,`has_quests`),
  KEY `shop_city_ready` (`shop_id`,`city_id`,`has_ready_quests`,`has_quests`),
  KEY `shop_cat_whole` (`shop_id`,`category_id`,`has_quests`),
  KEY `shop_cat_city` (`shop_id`,`category_id`,`city_id`,`has_quests`),
  KEY `shop_cat_ready` (`shop_id`,`category_id`,`has_ready_quests`,`has_quests`),
  KEY `shop_cat_city_ready` (`shop_id`,`category_id`,`city_id`,`has_ready_quests`,`has_quests`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods_packages
# ------------------------------------------------------------

CREATE TABLE `goods_packages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `good_id` int(11) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `measure` enum('mg','gr','kg','piece') COLLATE utf8_unicode_ci NOT NULL,
  `currency` enum('btc','rub','usd') COLLATE utf8_unicode_ci NOT NULL,
  `preorder` tinyint(1) NOT NULL DEFAULT '0',
  `preorder_time` enum('24','48','72') COLLATE utf8_unicode_ci DEFAULT NULL,
  `employee_reward` decimal(7,2) NOT NULL DEFAULT '0.00',
  `employee_penalty` decimal(7,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_id` (`shop_id`,`good_id`,`amount`,`measure`,`preorder`),
  KEY `goods_packages_shop_id_index` (`shop_id`),
  KEY `goods_packages_good_id_index` (`good_id`),
  KEY `goods_packages_shop_id_good_id_index` (`shop_id`,`good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods_packages_services
# ------------------------------------------------------------

CREATE TABLE `goods_packages_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_id` (`package_id`,`service_id`),
  KEY `goods_packages_services_service_id_index` (`service_id`),
  KEY `goods_packages_services_package_id_index` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods_photos
# ------------------------------------------------------------

CREATE TABLE `goods_photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `image_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_photos_good_id_index` (`good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods_positions
# ------------------------------------------------------------

CREATE TABLE `goods_positions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `subregion_id` int(11) DEFAULT NULL,
  `custom_place_id` int(11) DEFAULT NULL,
  `quest` text COLLATE utf8_unicode_ci NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_positions_good_id_index` (`good_id`),
  KEY `goods_positions_package_id_index` (`package_id`),
  KEY `goods_positions_employee_id_index` (`employee_id`),
  KEY `goods_positions_available_index` (`available`),
  KEY `goods_positions_good_id_package_id_index` (`good_id`,`package_id`),
  KEY `buy` (`good_id`,`package_id`,`available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table goods_reviews
# ------------------------------------------------------------

CREATE TABLE `goods_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `text` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `shop_rating` smallint(5) unsigned NOT NULL,
  `dropman_rating` smallint(5) unsigned NOT NULL,
  `item_rating` smallint(5) unsigned NOT NULL,
  `reply_text` mediumtext COLLATE utf8_unicode_ci,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_reviews_good_id_index` (`good_id`),
  KEY `goods_reviews_user_id_index` (`user_id`),
  KEY `goods_reviews_order_id_index` (`order_id`),
  KEY `goods_reviews_good_id_hidden_index` (`good_id`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table incomes
# ------------------------------------------------------------

CREATE TABLE `incomes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `amount_usd` double NOT NULL,
  `amount_btc` double(16,8) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table messages
# ------------------------------------------------------------

CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table migrations
# ------------------------------------------------------------

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table operations
# ------------------------------------------------------------

CREATE TABLE `operations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(16,8) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operations_wallet_id_index` (`wallet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table orders
# ------------------------------------------------------------

CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `review_id` int(11) DEFAULT NULL,
  `good_id` int(11) NOT NULL,
  `good_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `good_image_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `package_id` int(11) NOT NULL,
  `package_amount` decimal(8,2) NOT NULL,
  `package_measure` enum('gr','kg','mg','piece') COLLATE utf8_unicode_ci NOT NULL,
  `package_price` decimal(16,8) NOT NULL,
  `package_currency` enum('btc','rub','usd') COLLATE utf8_unicode_ci NOT NULL,
  `package_price_btc` decimal(16,8) NOT NULL,
  `package_preorder` tinyint(1) NOT NULL,
  `package_preorder_time` enum('24','48','72') COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('preorder_paid','paid','problem','finished','reserved','qiwi_paid') COLLATE utf8_unicode_ci NOT NULL,
  `status_was_problem` tinyint(1) NOT NULL,
  `guarantee` tinyint(1) NOT NULL,
  `comment` mediumtext COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_shop_id_index` (`shop_id`),
  KEY `orders_position_id_index` (`position_id`),
  KEY `orders_review_id_index` (`review_id`),
  KEY `orders_good_id_index` (`good_id`),
  KEY `orders_shop_id_good_id_index` (`shop_id`,`good_id`),
  KEY `orders_shop_id_user_id_index` (`shop_id`,`user_id`),
  KEY `orders_status_index` (`status`),
  KEY `orders_status_updated_at_index` (`status`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table orders_services
# ------------------------------------------------------------

CREATE TABLE `orders_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `price` double(16,8) NOT NULL,
  `currency` enum('rub','btc','usd') COLLATE utf8_unicode_ci NOT NULL,
  `price_btc` double(16,8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_services_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table pages
# ------------------------------------------------------------

CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pages_shop_id_index` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table paid_services
# ------------------------------------------------------------

CREATE TABLE `paid_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `price` double(16,8) NOT NULL,
  `currency` enum('rub','btc','usd') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `paid_services_shop_id_index` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table participants
# ------------------------------------------------------------

CREATE TABLE `participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_read` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `participants_thread_id_index` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table password_resets
# ------------------------------------------------------------

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table payouts
# ------------------------------------------------------------

CREATE TABLE `payouts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` double(16,8) NOT NULL,
  `method` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `route` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `result` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payouts_wallet_id_index` (`wallet_id`),
  KEY `payouts_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table qiwi_transactions
# ------------------------------------------------------------

CREATE TABLE `qiwi_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qiwi_wallet_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` double(16,8) NOT NULL,
  `sender` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('reserved','paid') COLLATE utf8_unicode_ci NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiwi_transactions_order_id_index` (`order_id`),
  KEY `qiwi_transactions_qiwi_wallet_id_index` (`qiwi_wallet_id`),
  KEY `qiwi_transactions_status_index` (`status`),
  KEY `qiwi_transactions_sender_index` (`sender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table qiwi_wallets
# ------------------------------------------------------------

CREATE TABLE `qiwi_wallets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `login` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double(16,8) NOT NULL,
  `reserved_balance` double(16,8) NOT NULL,
  `daily_limit` double(16,8) NOT NULL DEFAULT '0.00000000',
  `current_day_income` double(16,8) NOT NULL DEFAULT '0.00000000',
  `current_month_income` double(16,8) NOT NULL DEFAULT '0.00000000',
  `monthly_limit` double(16,8) NOT NULL DEFAULT '0.00000000',
  `status` enum('active','dead') COLLATE utf8_unicode_ci NOT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiwi_wallets_shop_id_index` (`shop_id`),
  KEY `qiwi_wallets_shop_id_status_last_checked_at_index` (`shop_id`,`status`,`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table regions
# ------------------------------------------------------------

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

CREATE TABLE `shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `banner_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `information` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `problem` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `plan` enum('basic','advanced','individual') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'basic',
  `employees_count` int(11) NOT NULL DEFAULT '0',
  `qiwi_count` int(11) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shops_slug_unique` (`slug`),
  KEY `shops_slug_index` (`slug`),
  KEY `shops_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table threads
# ------------------------------------------------------------

CREATE TABLE `threads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table transactions
# ------------------------------------------------------------

CREATE TABLE `transactions` (
  `tx_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_id` int(11) DEFAULT NULL,
  `address` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `handled` tinyint(1) NOT NULL DEFAULT '0',
  `confirmations` smallint(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tx_id`),
  KEY `transactions_address_index` (`address`),
  KEY `transactions_user_id_confirmations_index` (`confirmations`),
  KEY `transactions_wallet_id_index` (`wallet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table users
# ------------------------------------------------------------

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `totp_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contacts_other` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contacts_telegram` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contacts_jabber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `role` enum('admin','user','shop','shop_pending') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table wallets
# ------------------------------------------------------------

CREATE TABLE `wallets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double(16,8) NOT NULL DEFAULT '0.00000000',
  `type` enum('primary','additional') COLLATE utf8_unicode_ci NOT NULL,
  `wallet` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_key` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallets_shop_id_index` (`shop_id`),
  KEY `wallets_user_id_index` (`user_id`),
  KEY `wallets_shop_id_type_index` (`shop_id`,`type`),
  KEY `wallets_user_id_type_index` (`user_id`,`type`),
  KEY `wallets_wallet_index` (`wallet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;