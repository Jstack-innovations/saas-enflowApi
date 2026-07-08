-- Adminer 5.4.1 MySQL 9.4.0 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE `admin_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `admin_sessions` (`id`, `admin_id`, `token`, `created_at`, `last_activity`, `expires_at`) VALUES
(102,	2,	'13bce7208b79dc2db974950ffd736072cdcc17acc6b5bd12daf2a4403192231d',	'2026-06-15 17:23:09',	'2026-06-15 17:25:11',	'2026-06-15 17:55:11'),
(103,	2,	'7f4d364cabfb7add169ded99b78e2a24a8f9b84eebf6ab61e0ffe510d76517a1',	'2026-06-16 21:42:53',	'2026-06-16 21:53:50',	'2026-06-16 22:23:50'),
(104,	2,	'be1d84fcd577f530d1bf845810a931f45e5ca1789ed5287fc889291c0bc05212',	'2026-06-17 21:41:11',	'2026-06-17 21:41:34',	'2026-06-17 22:11:34'),
(105,	2,	'e3561d3b38a4773280201d139d1f148e30788631954dfd687fbebe81286cb6ac',	'2026-06-18 04:42:22',	'2026-06-18 04:49:07',	'2026-06-18 05:19:07'),
(106,	2,	'2adc03e013bae8e5db560af75322e415c873173f2e064bc657cb4a0ed4bd233d',	'2026-06-18 05:44:17',	'2026-06-18 05:57:13',	'2026-06-18 06:27:13'),
(107,	2,	'7a356c7b4c6d190fd12e8e83c47c0861329706e9f020f5b24aa8c8974f062585',	'2026-06-18 20:17:01',	'2026-06-18 20:21:00',	'2026-06-18 20:51:00');

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1,	'powells@ccjitters.com',	'Sams091#',	'2026-01-18 21:10:32'),
(2,	'Wsamson630@gmail.com',	'Sams091#',	'2026-01-18 21:10:32');

DROP TABLE IF EXISTS `booked_tables`;
CREATE TABLE `booked_tables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_id` int NOT NULL,
  `booked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_id` (`table_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `booked_tables` (`id`, `table_id`, `booked`) VALUES
(97,	445,	1),
(96,	67,	1),
(95,	45,	1),
(94,	78,	1),
(93,	567,	1),
(92,	56,	1),
(91,	2,	1),
(90,	114,	1),
(89,	334,	1),
(88,	116,	1),
(87,	112,	1),
(86,	113,	1);

DROP TABLE IF EXISTS `kitchen_production`;
CREATE TABLE `kitchen_production` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_id` int NOT NULL,
  `menu_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `note` text,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `kitchen_production` (`id`, `menu_id`, `menu_name`, `category`, `quantity`, `note`, `status`, `created_at`) VALUES
(18,	1,	'Authentic Nigerian Beef Suya',	'kitchen',	100,	'',	'done',	'2026-05-31 22:05:31'),
(19,	1,	'Authentic Nigerian Beef Suya',	'kitchen',	1,	'',	'done',	'2026-06-05 16:29:18');

DROP TABLE IF EXISTS `login_verifications`;
CREATE TABLE `login_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(4) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `menu_stock`;
CREATE TABLE `menu_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_id` int NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu_stock` (`id`, `menu_id`, `stock`, `available`, `updated_at`) VALUES
(1,	1,	90,	1,	'2026-06-13 13:31:54'),
(2,	2,	0,	0,	'2026-06-13 07:55:51'),
(3,	3,	20,	1,	'2026-04-12 14:59:16'),
(4,	4,	11,	1,	'2026-06-06 20:17:15'),
(5,	5,	17,	1,	'2026-06-18 12:03:54'),
(6,	6,	20,	1,	'2026-04-12 14:59:16'),
(7,	7,	11,	1,	'2026-05-31 22:01:15'),
(8,	8,	14,	1,	'2026-06-06 15:19:39'),
(9,	9,	20,	1,	'2026-04-12 14:59:16'),
(10,	10,	18,	1,	'2026-06-06 15:19:39'),
(11,	11,	20,	1,	'2026-04-12 14:59:16'),
(12,	12,	20,	1,	'2026-04-12 14:59:16'),
(13,	13,	19,	1,	'2026-05-01 23:43:39'),
(14,	14,	19,	1,	'2026-05-01 15:32:18'),
(15,	15,	20,	1,	'2026-04-12 14:59:16'),
(16,	16,	18,	1,	'2026-05-05 11:45:57'),
(17,	17,	19,	1,	'2026-05-01 15:32:22'),
(18,	18,	20,	1,	'2026-04-12 14:59:16'),
(19,	19,	15,	1,	'2026-06-13 06:39:13'),
(20,	20,	18,	1,	'2026-05-02 12:43:13'),
(21,	21,	20,	1,	'2026-04-12 14:59:16'),
(22,	22,	19,	1,	'2026-05-01 15:41:04'),
(23,	23,	19,	1,	'2026-05-01 15:41:05'),
(24,	24,	19,	1,	'2026-05-01 15:41:09'),
(25,	25,	19,	1,	'2026-05-01 15:41:10'),
(26,	26,	20,	1,	'2026-04-12 14:59:16'),
(27,	27,	20,	1,	'2026-04-12 14:59:16'),
(28,	28,	19,	1,	'2026-05-05 11:32:19'),
(29,	29,	20,	1,	'2026-04-12 14:59:16');

DROP TABLE IF EXISTS `paid_order_items`;
CREATE TABLE `paid_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `paid_order_id` int DEFAULT NULL,
  `menu_id` int DEFAULT NULL,
  `menu_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paid_order_id` (`paid_order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `paid_order_items` (`id`, `paid_order_id`, `menu_id`, `menu_name`, `price`, `quantity`) VALUES
(243,	245,	5,	'Nigerian Pepper Soup (Chicken / Goat)',	26.00,	1),
(242,	244,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(241,	243,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(240,	242,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(239,	241,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(238,	241,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(237,	240,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(236,	239,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(235,	238,	10,	'Spanish Patatas Bravas',	48.00,	1),
(234,	238,	8,	'Kenyan Ugali & Sukuma Wiki',	36.00,	1),
(233,	237,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(232,	236,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(231,	235,	2,	'Smoked Turkey Wings (Signature)',	24.00,	2),
(230,	235,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(229,	232,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(228,	231,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(227,	230,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(226,	230,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(225,	230,	7,	'Jamaican Ackee & Saltfish',	28.00,	1),
(224,	228,	2,	'Smoked Turkey Wings (Signature)',	24.00,	4);

DROP TABLE IF EXISTS `paid_orders`;
CREATE TABLE `paid_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `table_no` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `plate_order_no` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `order_type` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'table',
  `status` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'payment_pending',
  `full_address` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `order_status` enum('Order placed','Cooking','Cooking done','Out for delivery','Delivered','Served','Picked up') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'Order placed',
  `user_id` int DEFAULT NULL,
  `pickup_time` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `session_code` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_order` (`user_id`),
  CONSTRAINT `fk_user_order` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `paid_orders` (`id`, `name`, `phone`, `table_no`, `total_amount`, `payment_ref`, `created_at`, `plate_order_no`, `order_type`, `status`, `full_address`, `order_status`, `user_id`, `pickup_time`, `session_code`) VALUES
(200,	'Sdk',	'+2347089913116',	'990',	50.25,	'10200869',	'2026-05-03 13:05:22',	'Artisan20260503GRILL37',	'table',	'paid',	'',	'Delivered',	NULL,	'',	NULL),
(201,	'Vvddx',	'+2347089913116',	'78',	50.25,	'10200872',	'2026-05-03 13:06:44',	'Artisan20260503GRILL73',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(202,	'Samson',	'+2347089913116',	'112',	8.26,	NULL,	'2026-05-03 15:29:07',	'Artisan20260503GRILL20',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(203,	'Sam',	'+2342809943116',	'12',	4.13,	NULL,	'2026-05-03 16:17:04',	'Artisan20260503GRILL14',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(204,	'Dam',	'+2347089913116',	'14',	4.13,	NULL,	'2026-05-03 16:31:03',	'Artisan20260503GRILL35',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(205,	'Movo1',	'+2347089913116',	'78',	4.13,	NULL,	'2026-05-03 16:36:58',	'Artisan20260503GRILL52',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(206,	'Movo1',	'+2347089913116',	'112',	4.13,	NULL,	'2026-05-03 16:52:19',	'Artisan20260503GRILL75',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(207,	'Movo11',	'+2347089913116',	'17',	4.13,	'10201177',	'2026-05-03 17:11:20',	'Artisan20260503GRILL43',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(208,	'Movo12',	'+2347089913116',	'67',	4.13,	'10201179',	'2026-05-03 17:12:45',	'Artisan20260503GRILL15',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(209,	'Samlater',	'+2347089913116',	'57',	4.13,	'10203861',	'2026-05-05 06:02:38',	'Artisan20260505GRILL11',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-57-ED74B'),
(210,	'Sam2later',	'+2347089913116',	'57',	4.13,	'10203864',	'2026-05-05 06:04:27',	'Artisan20260505GRILL97',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-57-32BB4'),
(228,	'_black\'S',	'+2347089913116',	'113',	111.00,	'10265808',	'2026-05-31 21:57:13',	'Artisan20260531GRILL89',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(230,	'_black\'S',	'+2348096831043',	'112',	100.50,	'10265815',	'2026-05-31 22:01:15',	'Artisan20260531GRILL35',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-112-83D0D'),
(231,	'Sam',	'+2347089913116',	'116',	5.25,	'10276166',	'2026-06-05 05:36:46',	'Artisan20260605GRILL73',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(232,	'Bashir',	'+2347048566270',	'',	30.00,	NULL,	'2026-06-05 10:39:27',	'Artisan20260605GRILL37',	'pickup',	'payment_pending',	'',	'Order placed',	NULL,	'12:30',	NULL),
(234,	'Victoria Okon',	'08037640426',	'7',	111.00,	'None',	'2026-06-05 21:38:54',	'2',	'Dining ',	'payment_pending',	'Road 7 extension Imo housing Estate',	'Order placed',	NULL,	NULL,	NULL),
(235,	'Blessing S',	'+2348144890961',	'',	58.13,	NULL,	'2026-06-05 22:41:23',	'Artisan20260605GRILL81',	'pickup',	'payment_pending',	'',	'Order placed',	NULL,	'12:00',	NULL),
(236,	'b\'S',	'+2347089913116',	'334',	4.13,	'10279065',	'2026-06-06 13:08:13',	'Artisan20260606GRILL27',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(237,	'Sass',	'+07089913116',	'114',	4.13,	'10279081',	'2026-06-06 13:21:40',	'Artisan20260606GRILL77',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(238,	'Victoria Okon',	'+2348037640426',	'',	97.50,	'10279189',	'2026-06-06 15:19:20',	'Artisan20260606GRILL23',	'delivery',	'paid',	'Road 7 extension Imo housing Estate',	'Order placed',	NULL,	'',	NULL),
(239,	'Ssd ',	'+2347089913116',	'56',	4.13,	'10279198',	'2026-06-06 15:25:19',	'Artisan20260606GRILL53',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(240,	'Bbb',	'+2348096831043',	'567',	4.13,	'10279537',	'2026-06-06 20:14:53',	'Artisan20260606GRILL87',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(241,	'Ccfcc',	'+2348096831043',	'78',	47.13,	'10279540',	'2026-06-06 20:16:55',	'Artisan20260606GRILL87',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-78-70C84'),
(242,	'Gh',	'+2348096831043',	'45',	4.13,	'10293881',	'2026-06-12 11:05:09',	'Artisan20260612GRILL20',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(243,	'Ft',	'+2348096831043',	'78',	4.13,	'10295063',	'2026-06-13 02:00:23',	'Artisan20260613GRILL56',	'table',	'paid',	'',	'Delivered',	NULL,	'',	NULL),
(244,	'Samss',	'+2347089913116',	'67',	4.13,	'10296155',	'2026-06-13 13:31:36',	'Artisan20260613GRILL19',	'table',	'paid',	'',	'Served',	NULL,	'',	NULL),
(245,	'Ddf',	'+2348096831043',	'445',	32.25,	'10308456',	'2026-06-18 12:03:33',	'Artisan20260618GRILL01',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL);

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `booking_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` int DEFAULT '1',
  `reservation_code` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `reservations` (`id`, `table_id`, `name`, `email`, `phone`, `booking_date`, `amount`, `transaction_id`, `status`, `reservation_code`, `created_at`) VALUES
(34,	2,	'Victoria Okon',	'rolexandialways@gmail.com',	'+2348037640426',	'2026-06-06 15:15:00',	3000.00,	'10279183',	1,	'RES-ART-618E0151',	'2026-06-06 15:16:37');

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','active') DEFAULT 'pending',
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2026-06-21 09:46:35 UTC
