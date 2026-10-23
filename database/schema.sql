-- Janahitha Bake House: MySQL / MariaDB database schema (all tables, keys and indexes).
-- Import this file into an EMPTY database, for example with phpMyAdmin > Import.
-- It also records the migrations as run, so `php artisan migrate` later only applies newer ones.
-- Regenerate after schema changes with: php artisan schema:dump --path=database/schema.sql

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `bill_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int unsigned NOT NULL,
  `ret` int unsigned NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_lines_bill_id_foreign` (`bill_id`),
  KEY `bill_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `bill_lines_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bill_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `route_id` bigint unsigned NOT NULL,
  `shop_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `shop_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'due',
  `paid_at` timestamp NULL DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_date_shop_id_unique` (`date`,`shop_id`),
  KEY `bills_route_id_foreign` (`route_id`),
  KEY `bills_shop_id_foreign` (`shop_id`),
  KEY `bills_order_id_foreign` (`order_id`),
  KEY `bills_date_route_id_index` (`date`,`route_id`),
  CONSTRAINT `bills_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bills_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sort` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_source_key_unique` (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_lines_order_id_item_id_unique` (`order_id`,`item_id`),
  KEY `order_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `order_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_lines_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `route_id` bigint unsigned NOT NULL,
  `shop_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_date_shop_id_unique` (`date`,`shop_id`),
  KEY `orders_route_id_foreign` (`route_id`),
  KEY `orders_shop_id_foreign` (`shop_id`),
  KEY `orders_created_by_foreign` (`created_by`),
  KEY `orders_date_route_id_index` (`date`,`route_id`),
  CONSTRAINT `orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_created_by_foreign` (`created_by`),
  KEY `payments_bill_id_created_at_index` (`bill_id`,`created_at`),
  CONSTRAINT `payments_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `route_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `route_user` (
  `route_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`route_id`,`user_id`),
  KEY `route_user_user_id_foreign` (`user_id`),
  CONSTRAINT `route_user_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routes_source_key_unique` (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_items` (
  `shop_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `sort` smallint unsigned NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`shop_id`,`item_id`),
  KEY `shop_items_item_id_foreign` (`item_id`),
  CONSTRAINT `shop_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shop_items_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shops` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_id` bigint unsigned NOT NULL,
  `sort` smallint unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shops_source_key_unique` (`source_key`),
  KEY `shops_route_id_sort_index` (`route_id`,`sort`),
  CONSTRAINT `shops_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `perms` json DEFAULT NULL,
  `all_routes` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `is_owner` tinyint(1) NOT NULL DEFAULT '0',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legacy_password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_password_salt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_source_key_unique` (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_10_12_162700_create_bakery_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_10_14_172511_add_document_sync_columns',1);

-- Default bakery seed data (routes, items, shops, shop_items)
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (1, 'sujith', 'Sujith', 'Sujith', 1, NOW(), NOW());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (2, 'boss', 'Boss', 'Boss', 2, NOW(), NOW());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (3, 'darshana', 'Darshana', 'Darshana', 3, NOW(), NOW());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (4, 'route4', 'Route 4', NULL, 4, NOW(), NOW());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (5, 'mama', 'Mama', 'Mama', 5, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (1, 'fish', 'Fish', '0', 1, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (2, 'bread', 'Bread', '0', 2, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (3, 'jam', 'Jam', '0', 3, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (4, 'kimbula', 'Kimbula', '0', 4, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (5, 'keels', 'Keels', '0', 5, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (6, 'keels_s', '* Keels', '0', 6, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (7, 'kala', 'Kala', '0', 7, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (8, 'pastry', 'Pastry', '0', 8, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (9, 'saman', 'Saman', '0', 9, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (10, 'ada', 'Ada', '0', 10, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (11, 'rcake', 'R Cake', '0', 11, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (12, 'vcake', 'V Cake', '0', 12, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (13, 'sini', 'Sini', '0', 13, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (14, 'multi', 'Multi', '0', 14, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (15, 'dagara', 'Dagara', '0', 15, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (16, 'sinisam', 'Sini Sambal', '0', 16, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (17, 'egg', 'Egg', '0', 17, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (18, 'sp', 'SP', '0', 18, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (19, 'milo', 'Milo Box', '0', 19, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (20, 'proll', 'P Roll', '0', 20, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (21, 'kp', 'KP', '0', 21, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (22, 'bp', 'BP', '0', 22, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (23, 'cream', 'Cream', '0', 23, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (24, 'double', 'Double', '0', 24, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (25, 'pizza', 'Pizza', '0', 25, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (26, 'cake', 'Cake', '0', 26, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (27, 'tbun', 'T Bun', '0', 27, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (28, 'bola', 'Bola', '0', 28, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (29, 'sliced', 'Sliced', '0', 29, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (30, 'family', 'Family', '0', 30, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (31, 'nice', 'Nice', '0', 31, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (32, 'achchu', 'Achchu', '0', 32, NOW(), NOW());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (33, 'thati', 'Thati', '0', 33, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (1, 'sujith-01', 'Jawaththa', 1, 1, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (2, 'sujith-02', 'J. Handiya', 1, 2, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (3, 'sujith-03', 'Es Wattuwa', 1, 3, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (4, 'sujith-04', 'Asiri', 1, 4, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (5, 'sujith-05', 'Police Park', 1, 5, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (6, 'sujith-06', 'Kalubowila', 1, 6, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (7, 'sujith-07', 'P Roll', 1, 7, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (8, 'sujith-08', 'Marad. Ashoka', 1, 8, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (9, 'boss-01', 'Soysa Laga', 2, 1, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (10, 'boss-02', 'Usawiya', 2, 2, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (11, 'boss-03', 'Police S M', 2, 3, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (12, 'boss-04', 'Central 3', 2, 4, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (13, 'boss-05', 'Ranjani OPD', 2, 5, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (14, 'boss-06', 'Medical Faculty', 2, 6, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (15, 'boss-07', 'Wasana 2', 2, 7, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (16, 'boss-08', 'Colombo Campus', 2, 8, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (17, 'boss-09', 'Central Akka', 2, 9, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (18, 'boss-10', 'Paththara Kade', 2, 10, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (19, 'boss-11', 'Cafe 7', 2, 11, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (20, 'boss-12', 'Lorry', 2, 12, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (21, 'darshana-01', 'Maradana 2', 3, 1, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (22, 'darshana-02', 'Maradana Station', 3, 2, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (23, 'darshana-03', 'Thunmulla', 3, 3, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (24, 'darshana-04', 'Maradana 4', 3, 4, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (25, 'darshana-05', 'Maradana Pansala', 3, 5, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (26, 'darshana-06', 'Mahen', 3, 6, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (27, 'darshana-07', 'Dublication', 3, 7, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (28, 'darshana-08', 'Dublication 2', 3, 8, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (29, 'darshana-09', 'Maradana New', 3, 9, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (30, 'darshana-10', 'Maradana Main', 3, 10, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (31, 'darshana-11', 'Royal Laga', 3, 11, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (32, 'route4-01', 'Marad. Technical', 4, 1, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (33, 'route4-02', 'Acland', 4, 2, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (34, 'route4-03', 'Kotuwa', 4, 3, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (35, 'route4-04', 'New Shop', 4, 4, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (36, 'route4-05', 'Li Balika / College', 4, 5, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (37, 'route4-06', 'Ragama', 4, 6, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (38, 'route4-07', 'Araliya Canteen', 4, 7, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (39, 'route4-08', 'RNR', 4, 8, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (40, 'route4-09', 'RO (Office / Ground)', 4, 9, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (41, 'route4-10', 'Technical Department', 4, 10, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (42, 'route4-11', 'Port City', 4, 11, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (43, 'route4-12', 'Waraya (Port City)', 4, 12, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (44, 'mama-01', 'Dippitigoda', 5, 1, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (45, 'mama-02', 'Campus', 5, 2, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (46, 'mama-03', 'Pethiyagoda', 5, 3, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (47, 'mama-04', 'Logis', 5, 4, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (48, 'mama-05', 'Dr Upul', 5, 5, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (49, 'mama-06', 'Bulugaha Handiya', 5, 6, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (50, 'mama-07', 'Viharamahadevi', 5, 7, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (51, 'mama-08', 'Pansal Handiya', 5, 8, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (52, 'mama-09', 'ROY', 5, 9, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (53, 'mama-10', 'Kanaththa Laga', 5, 10, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (54, 'mama-11', 'Gedara Aiya', 5, 11, 1, NOW(), NOW());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (55, 'mama-12', 'Thorana Handiya', 5, 12, 1, NOW(), NOW());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 7, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 9, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 10, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 11, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 12, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 13, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 14, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 15, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 16, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 8, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 2, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 7, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 21, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 22, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 1, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 5, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 18, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 23, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 24, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 16, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 6, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 17, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 9, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 14, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 15, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 13, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 7, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 10, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 18, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 12, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 11, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 24, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 23, 20, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 19, 21, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 8, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 22, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 25, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 21, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 17, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 18, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 10, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 26, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 23, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 24, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 25, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 28, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 3, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 14, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 15, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 13, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 29, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 9, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 28, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 3, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 9, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 24, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 23, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 11, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 18, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 23, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 24, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 25, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 18, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 11, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 12, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 1, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 8, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 22, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 21, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 3, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 4, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 17, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 9, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 24, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 30, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 27, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 24, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 9, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 16, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 3, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 18, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 8, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 16, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 9, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 7, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 2, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 10, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 23, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 24, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 11, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 12, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 8, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 30, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 18, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 21, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 22, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 5, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 20, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 11, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 12, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 18, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 10, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 25, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 9, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 16, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 17, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 6, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 26, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 29, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 24, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 23, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 24, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 1, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (21, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (21, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (22, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (22, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 14, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 8, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 20, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 17, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 2, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 7, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 22, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 13, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 21, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 24, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 23, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 10, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 11, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 12, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 27, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 21, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 24, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 30, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 6, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 3, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 17, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 18, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 23, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 24, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 19, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 20, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 28, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 7, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 21, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 22, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 5, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 11, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 12, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 10, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 27, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 7, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 20, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 16, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 10, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 28, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 1, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 21, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 22, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 13, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 10, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 24, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 29, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 11, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 18, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 31, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 19, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 5, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 6, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 17, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 8, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 21, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 22, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 23, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 24, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 30, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 11, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 12, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 18, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 24, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 23, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 16, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 17, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 13, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 14, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 11, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 22, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 7, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 2, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 2, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 25, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 28, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 8, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 11, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 7, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 8, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 11, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 18, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 29, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 20, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 31, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 14, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 15, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 13, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 6, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 17, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 4, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 3, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 8, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 21, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 22, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 2, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 10, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 18, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 11, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 12, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 23, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 24, 20, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 27, 21, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 25, 22, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 7, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 20, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 28, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 29, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 8, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 16, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 17, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 17, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 22, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 11, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 12, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 10, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 24, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 23, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 30, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 6, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 22, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 23, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 24, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 10, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 11, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 12, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 8, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 6, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 24, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 29, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 25, 20, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 2, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 21, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 22, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 16, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 6, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 8, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 25, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 8, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 9, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 21, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 22, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 28, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 16, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 10, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 6, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 8, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 25, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 22, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 25, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 14, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 15, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 13, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 16, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 1, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 17, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 1, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 24, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 29, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 23, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 7, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 25, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 7, 6, NULL);

-- Default routes, items, and shops from data/seed.json
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (1, 'sujith', 'Sujith', 'Sujith', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (2, 'boss', 'Boss', 'Boss', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (3, 'darshana', 'Darshana', 'Darshana', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (4, 'route4', 'Route 4', NULL, 4, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `routes` (`id`, `source_key`, `name`, `driver`, `sort`, `created_at`, `updated_at`) VALUES (5, 'mama', 'Mama', 'Mama', 5, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (1, 'fish', 'Fish', '0.00', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (2, 'bread', 'Bread', '0.00', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (3, 'jam', 'Jam', '0.00', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (4, 'kimbula', 'Kimbula', '0.00', 4, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (5, 'keels', 'Keels', '0.00', 5, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (6, 'keels_s', '* Keels', '0.00', 6, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (7, 'kala', 'Kala', '0.00', 7, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (8, 'pastry', 'Pastry', '0.00', 8, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (9, 'saman', 'Saman', '0.00', 9, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (10, 'ada', 'Ada', '0.00', 10, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (11, 'rcake', 'R Cake', '0.00', 11, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (12, 'vcake', 'V Cake', '0.00', 12, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (13, 'sini', 'Sini', '0.00', 13, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (14, 'multi', 'Multi', '0.00', 14, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (15, 'dagara', 'Dagara', '0.00', 15, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (16, 'sinisam', 'Sini Sambal', '0.00', 16, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (17, 'egg', 'Egg', '0.00', 17, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (18, 'sp', 'SP', '0.00', 18, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (19, 'milo', 'Milo Box', '0.00', 19, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (20, 'proll', 'P Roll', '0.00', 20, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (21, 'kp', 'KP', '0.00', 21, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (22, 'bp', 'BP', '0.00', 22, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (23, 'cream', 'Cream', '0.00', 23, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (24, 'double', 'Double', '0.00', 24, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (25, 'pizza', 'Pizza', '0.00', 25, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (26, 'cake', 'Cake', '0.00', 26, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (27, 'tbun', 'T Bun', '0.00', 27, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (28, 'bola', 'Bola', '0.00', 28, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (29, 'sliced', 'Sliced', '0.00', 29, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (30, 'family', 'Family', '0.00', 30, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (31, 'nice', 'Nice', '0.00', 31, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (32, 'achchu', 'Achchu', '0.00', 32, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `items` (`id`, `source_key`, `name`, `default_price`, `sort`, `created_at`, `updated_at`) VALUES (33, 'thati', 'Thati', '0.00', 33, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (1, 'sujith-01', 'Jawaththa', 1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 7, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (1, 9, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (2, 'sujith-02', 'J. Handiya', 1, 2, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 10, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 11, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (2, 12, 11, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (3, 'sujith-03', 'Es Wattuwa', 1, 3, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 13, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 14, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 15, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 16, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 8, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 2, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (3, 7, 12, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (4, 'sujith-04', 'Asiri', 1, 4, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 21, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 22, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 1, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 5, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 18, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 23, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (4, 24, 11, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (5, 'sujith-05', 'Police Park', 1, 5, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 16, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 6, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 17, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 9, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 14, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 15, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 13, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 7, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 10, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 18, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 12, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 11, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 24, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 23, 20, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (5, 19, 21, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (6, 'sujith-06', 'Kalubowila', 1, 6, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 8, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 22, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (6, 25, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (7, 'sujith-07', 'P Roll', 1, 7, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 21, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 17, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 18, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 10, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 26, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 23, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 24, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (7, 25, 9, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (8, 'sujith-08', 'Marad. Ashoka', 1, 8, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 28, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 3, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 14, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 15, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 13, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (8, 29, 11, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (9, 'boss-01', 'Soysa Laga', 2, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (9, 9, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (10, 'boss-02', 'Usawiya', 2, 2, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 28, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 3, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 9, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 24, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 23, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 11, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (10, 18, 13, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (11, 'boss-03', 'Police S M', 2, 3, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 23, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 24, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 25, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 18, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 11, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 12, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 1, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 8, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 22, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 21, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 3, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (11, 4, 12, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (12, 'boss-04', 'Central 3', 2, 4, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 17, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 9, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 24, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (12, 30, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (13, 'boss-05', 'Ranjani OPD', 2, 5, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 27, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 24, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 9, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (13, 5, 5, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (14, 'boss-06', 'Medical Faculty', 2, 6, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 16, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 3, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 18, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (14, 8, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (15, 'boss-07', 'Wasana 2', 2, 7, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 16, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 9, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (15, 7, 7, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (16, 'boss-08', 'Colombo Campus', 2, 8, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 2, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (16, 8, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (17, 'boss-09', 'Central Akka', 2, 9, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 10, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 23, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 24, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 11, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 12, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 8, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 30, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 18, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 21, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (17, 22, 16, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (18, 'boss-10', 'Paththara Kade', 2, 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 5, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 20, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 11, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 12, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 18, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 10, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (18, 25, 13, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (19, 'boss-11', 'Cafe 7', 2, 11, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 9, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 16, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 17, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 6, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 26, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 29, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (19, 24, 10, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (20, 'boss-12', 'Lorry', 2, 12, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 23, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 24, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 1, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (20, 2, 7, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (21, 'darshana-01', 'Maradana 2', 3, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (21, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (21, 8, 2, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (22, 'darshana-02', 'Maradana Station', 3, 2, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (22, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (22, 8, 2, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (23, 'darshana-03', 'Thunmulla', 3, 3, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 14, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 8, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 20, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 17, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 2, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 7, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 22, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 13, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 21, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 24, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 23, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 10, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 11, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 12, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (23, 27, 19, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (24, 'darshana-04', 'Maradana 4', 3, 4, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 21, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 24, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (24, 30, 6, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (25, 'darshana-05', 'Maradana Pansala', 3, 5, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 5, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 6, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 3, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 17, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 18, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 23, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 24, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (25, 19, 13, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (26, 'darshana-06', 'Mahen', 3, 6, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 20, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 28, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 2, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 7, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 21, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 22, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 5, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 11, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 12, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (26, 10, 14, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (27, 'darshana-07', 'Dublication', 3, 7, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 27, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 5, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 4, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 8, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 7, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 20, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 16, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (27, 10, 11, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (28, 'darshana-08', 'Dublication 2', 3, 8, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 28, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 1, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 21, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 22, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 13, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 10, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 24, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 29, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 11, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 18, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 31, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (28, 19, 16, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (29, 'darshana-09', 'Maradana New', 3, 9, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 5, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 6, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 17, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 8, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 21, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 22, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 23, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 24, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 30, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 11, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 12, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (29, 18, 17, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (30, 'darshana-10', 'Maradana Main', 3, 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 24, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (30, 23, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (31, 'darshana-11', 'Royal Laga', 3, 11, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 16, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (31, 17, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (32, 'route4-01', 'Marad. Technical', 4, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 20, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 13, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 14, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (32, 11, 7, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (33, 'route4-02', 'Acland', 4, 2, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 21, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 22, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 7, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (33, 2, 6, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (34, 'route4-03', 'Kotuwa', 4, 3, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 2, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 27, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (34, 25, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (35, 'route4-04', 'New Shop', 4, 4, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 28, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 8, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 11, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (35, 7, 9, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (36, 'route4-05', 'Li Balika / College', 4, 5, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 8, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 11, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 18, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 29, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 20, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (36, 31, 9, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (37, 'route4-06', 'Ragama', 4, 6, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 14, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 15, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 13, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 6, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 17, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 4, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 3, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 8, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 21, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 22, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 2, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 10, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 18, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 11, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 12, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 23, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 24, 20, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 27, 21, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (37, 25, 22, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (38, 'route4-07', 'Araliya Canteen', 4, 7, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 7, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 20, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 28, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 29, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 8, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 9, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (38, 16, 12, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (39, 'route4-08', 'RNR', 4, 8, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (39, 4, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (40, 'route4-09', 'RO (Office / Ground)', 4, 9, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (41, 'route4-10', 'Technical Department', 4, 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 17, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (41, 1, 4, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (42, 'route4-11', 'Port City', 4, 11, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 4, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 17, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 9, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 16, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 22, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 11, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 12, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 10, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 24, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 23, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (42, 30, 16, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (43, 'route4-12', 'Waraya (Port City)', 4, 12, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 8, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 6, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 22, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 23, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 24, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 18, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 10, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 11, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (43, 12, 11, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (44, 'mama-01', 'Dippitigoda', 5, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 8, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 6, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 24, 18, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 29, 19, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (44, 25, 20, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (45, 'mama-02', 'Campus', 5, 2, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 2, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 21, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 22, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (45, 16, 10, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (46, 'mama-03', 'Pethiyagoda', 5, 3, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 6, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 8, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (46, 25, 18, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (47, 'mama-04', 'Logis', 5, 4, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 20, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 8, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 5, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 9, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 21, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (47, 22, 7, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (48, 'mama-05', 'Dr Upul', 5, 5, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 28, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 17, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 1, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 8, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 16, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (48, 10, 7, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (49, 'mama-06', 'Bulugaha Handiya', 5, 6, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 3, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 13, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 14, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 15, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 16, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 17, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 6, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 9, 12, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 5, 13, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 1, 14, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 8, 15, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 21, 16, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 22, 17, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (49, 25, 18, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (50, 'mama-07', 'Viharamahadevi', 5, 7, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 20, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 2, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 1, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 22, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 25, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 14, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 15, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (50, 13, 8, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (51, 'mama-08', 'Pansal Handiya', 5, 8, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 7, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 16, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (51, 1, 6, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (52, 'mama-09', 'ROY', 5, 9, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 32, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 33, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 4, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 17, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 9, 6, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 1, 7, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 5, 8, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 6, 9, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 24, 10, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 29, 11, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (52, 23, 12, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (53, 'mama-10', 'Kanaththa Laga', 5, 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 7, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (53, 4, 5, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (54, 'mama-11', 'Gedara Aiya', 5, 11, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 1, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 4, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 3, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 8, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 2, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (54, 7, 6, NULL);
INSERT IGNORE INTO `shops` (`id`, `source_key`, `name`, `route_id`, `sort`, `active`, `created_at`, `updated_at`) VALUES (55, 'mama-12', 'Thorana Handiya', 5, 12, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 2, 1, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 1, 2, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 4, 3, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 3, 4, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 25, 5, NULL);
INSERT IGNORE INTO `shop_items` (`shop_id`, `item_id`, `sort`, `price`) VALUES (55, 7, 6, NULL);
