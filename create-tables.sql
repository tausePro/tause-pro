-- Create product categories table
CREATE TABLE IF NOT EXISTS `ext_chatbot_product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `slug` varchar(255) NOT NULL UNIQUE,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ext_chatbot_product_categories_user_id_foreign` (`user_id`),
  CONSTRAINT `ext_chatbot_product_categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table
CREATE TABLE IF NOT EXISTS `ext_chatbot_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `sku` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `purchase_url` varchar(255) DEFAULT NULL,
  `availability` enum('in_stock','out_of_stock','discontinued') NOT NULL DEFAULT 'in_stock',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ext_chatbot_products_user_id_foreign` (`user_id`),
  KEY `ext_chatbot_products_category_id_foreign` (`category_id`),
  CONSTRAINT `ext_chatbot_products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ext_chatbot_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `ext_chatbot_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create analytics table
CREATE TABLE IF NOT EXISTS `ext_chatbot_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chatbot_id` bigint unsigned NOT NULL,
  `query` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'web',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ext_chatbot_analytics_chatbot_id_foreign` (`chatbot_id`),
  CONSTRAINT `ext_chatbot_analytics_chatbot_id_foreign` FOREIGN KEY (`chatbot_id`) REFERENCES `chatbots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;