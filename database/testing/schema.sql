/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `asset_coverages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_coverages` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `coverage_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__asset_id__coverage_id__deleted_not` (`asset_id`,`coverage_id`,`deleted_not`),
  KEY `fk_asset_coverages_coverages1_idx` (`coverage_id`),
  KEY `fk_asset_coverages_assets1_idx` (`asset_id`) /*!80000 INVISIBLE */,
  KEY `idx__deleted_at` (`deleted_at`) /*!80000 INVISIBLE */,
  CONSTRAINT `fk_asset_coverages_assets1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_coverages_coverages1` FOREIGN KEY (`coverage_id`) REFERENCES `coverages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_tags` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `tag_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__tag_id__asset_id__deleted_not` (`tag_id`,`asset_id`,`deleted_not`),
  KEY `fk_asset_tags_tags1_idx` (`tag_id`),
  KEY `fk_asset_tags_assets1_idx` (`asset_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__tag_id__deleted_at` (`tag_id`,`deleted_at`),
  KEY `idx__asset_id__deleted_at` (`asset_id`,`deleted_at`),
  CONSTRAINT `fk_asset_tags_assets1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_tags_tags1` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_warranties` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `asset_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `status_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `document_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `document_number` varchar(64) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `service_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `service_level_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_0900_as_ci,
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_warranties_assets1_idx` (`asset_id`),
  KEY `fk_asset_warranties_documents1_idx` (`document_id`),
  KEY `fk_asset_warranties_resellers1_idx` (`reseller_id`),
  KEY `fk_asset_warranties_customers1_idx` (`customer_id`),
  KEY `fk_asset_warranties_service_groups1_idx` (`service_group_id`),
  KEY `fk_asset_warranties_statuses1_idx` (`status_id`),
  KEY `fk_asset_warranties_types1_idx` (`type_id`),
  KEY `fk_asset_warranties_service_levels1_idx` (`service_level_id`),
  KEY `idx__deleted_at__asset_id` (`deleted_at`,`asset_id`),
  KEY `idx__end__deleted_at__asset_id` (`end`,`deleted_at`,`asset_id`),
  CONSTRAINT `fk_asset_warranties_assets1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_documents1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_service_groups1` FOREIGN KEY (`service_group_id`) REFERENCES `service_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_service_levels1` FOREIGN KEY (`service_level_id`) REFERENCES `service_levels` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_statuses1` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_asset_warranties_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `product_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL COMMENT 'current',
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL COMMENT 'current',
  `location_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL COMMENT 'current',
  `status_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `warranty_changed_at` timestamp NULL DEFAULT NULL,
  `warranty_service_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `warranty_service_level_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `data_quality` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `eosl` date DEFAULT NULL,
  `contracts_active_quantity` int DEFAULT NULL,
  `contacts_count` int NOT NULL DEFAULT '0',
  `coverages_count` int NOT NULL DEFAULT '0',
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_assets_oems1_idx` (`oem_id`),
  KEY `fk_assets_products1_idx` (`product_id`),
  KEY `fk_assets_customers1_idx` (`customer_id`),
  KEY `fk_assets_locations1_idx` (`location_id`),
  KEY `fk_assets_types1_idx` (`type_id`),
  KEY `fk_assets_resellers1_idx` (`reseller_id`),
  KEY `fk_assets_statuses1_idx` (`status_id`),
  KEY `idx__serial_number__deleted_at` (`serial_number`,`deleted_at`),
  KEY `idx__warranty_end__deleted_at` (`warranty_end`,`deleted_at`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__product_id__deleted_at` (`product_id`,`deleted_at`),
  KEY `idx__reseller_id__deleted_at` (`reseller_id`,`deleted_at`),
  KEY `idx__customer_id__deleted_at` (`customer_id`,`deleted_at`),
  KEY `idx__location_id__deleted_at` (`location_id`,`deleted_at`),
  KEY `idx__status_id__deleted_at` (`status_id`,`deleted_at`),
  KEY `idx__type_id__deleted_at` (`type_id`,`deleted_at`),
  KEY `idx__coverages_count__deleted_at` (`coverages_count`,`deleted_at`),
  KEY `idx__nickname__deleted_at` (`nickname`,`deleted_at`),
  KEY `fk_assets_service_groups1_idx` (`warranty_service_group_id`),
  KEY `fk_assets_service_levels1_idx` (`warranty_service_level_id`),
  KEY `idx__eosl__deleted_at` (`eosl`,`deleted_at`),
  FULLTEXT KEY `ftx__serial_number` (`serial_number`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ftx__nickname` (`nickname`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `fk_assets_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_locations1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_products1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_service_groups1` FOREIGN KEY (`warranty_service_group_id`) REFERENCES `service_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_service_levels1` FOREIGN KEY (`warranty_service_level_id`) REFERENCES `service_levels` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_statuses1` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_assets_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audits` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `object_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx__user_id` (`user_id`),
  KEY `idx__organization_id__user_id` (`organization_id`,`user_id`),
  KEY `idx__object_id__object_type` (`object_id`,`object_type`),
  KEY `idx__action__organization_id` (`action`,`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `change_requests` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `from` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `to` json NOT NULL,
  `cc` json DEFAULT NULL,
  `bcc` json DEFAULT NULL,
  `message` text COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_change_requests_organizations1_idx` (`organization_id`),
  KEY `fk_change_requests_users1_idx` (`user_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__object_id__object_type__deleted_at` (`object_id`,`object_type`,`deleted_at`),
  CONSTRAINT `fk_change_requests_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_change_requests_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `country_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__name__country_id__deleted_not` (`name`,`country_id`,`deleted_not`) /*!80000 INVISIBLE */,
  UNIQUE KEY `unique__key__country_id__deleted_not` (`key`,`country_id`,`deleted_not`),
  KEY `fk_data_cities_countries_idx` (`country_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_data_cities_countries` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_types` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `contact_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__contact_id__type_id__deleted_not` (`contact_id`,`type_id`,`deleted_not`),
  KEY `fk_contact_types_types1_idx` (`type_id`),
  KEY `fk_contact_types_contacts1_idx` (`contact_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_contact_types_contacts1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_contact_types_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `phone_number` varchar(64) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `phone_valid` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__name__phone__email__object_id__object_type__deleted_not` (`name`,`phone_number`,`email`,`object_id`,`object_type`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__object_id__object_type__deleted_at` (`object_id`,`object_type`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `code` char(2) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__code__deleted_not` (`code`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coverages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coverages` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `code` char(3) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__code__deleted_not` (`code`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_location_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_location_types` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customer_location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__type` (`customer_location_id`,`type_id`,`deleted_not`),
  KEY `fk_customer_location_types_types1_idx` (`type_id`),
  KEY `fk_customer_location_types_customer_locations1_idx` (`customer_location_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customer_location_types_customer_locations1` FOREIGN KEY (`customer_location_id`) REFERENCES `customer_locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_customer_location_types_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_locations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__location` (`customer_id`,`location_id`,`deleted_not`),
  KEY `fk_customer_locations_customers1_idx` (`customer_id`),
  KEY `fk_customer_locations_locations1_idx` (`location_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customer_locations_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_customer_locations_locations1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_statuses` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `status_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__customer_id__status_id__deleted_not` (`customer_id`,`status_id`,`deleted_not`),
  KEY `fk_customer_statuses_statuses1_idx` (`status_id`),
  KEY `fk_customer_statuses_customers1_idx` (`customer_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customer_statuses_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_customer_statuses_statuses1` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `kpi_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `assets_count` mediumint unsigned NOT NULL DEFAULT '0',
  `quotes_count` int unsigned NOT NULL DEFAULT '0',
  `contracts_count` int unsigned NOT NULL DEFAULT '0',
  `locations_count` int unsigned NOT NULL DEFAULT '0',
  `contacts_count` int unsigned NOT NULL DEFAULT '0',
  `statuses_count` int unsigned NOT NULL DEFAULT '0',
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__name__deleted_at` (`name`,`deleted_at`),
  KEY `idx__statuses_count__deleted_at` (`statuses_count`,`deleted_at`),
  KEY `fk_customers_kpis1_idx` (`kpi_id`),
  FULLTEXT KEY `ftx__name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `fk_customers_kpis1` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `distributors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `distributors` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_entries` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `document_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `asset_type_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `service_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `service_level_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `product_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `product_line_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `product_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `currency_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `list_price` decimal(12,2) DEFAULT NULL,
  `list_price_origin` decimal(12,2) DEFAULT NULL,
  `monthly_list_price` decimal(12,2) DEFAULT NULL,
  `monthly_list_price_origin` decimal(12,2) DEFAULT NULL,
  `monthly_retail_price` decimal(12,2) DEFAULT NULL,
  `monthly_retail_price_origin` decimal(12,2) DEFAULT NULL,
  `renewal` decimal(12,2) DEFAULT NULL,
  `renewal_origin` decimal(12,2) DEFAULT NULL,
  `oem_said` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_sar_number` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `psp_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `environment_id` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `equipment_number` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `language_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `removed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_document_entries_documents1_idx` (`document_id`),
  KEY `fk_document_entries_assets1_idx` (`asset_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_document_entries_currencies1_idx` (`currency_id`),
  KEY `fk_document_entries_products2_idx` (`product_id`),
  KEY `fk_document_entries_service_levels1_idx` (`service_level_id`),
  KEY `fk_document_entries_service_groups1_idx` (`service_group_id`),
  KEY `idx__document_id__deleted_at` (`document_id`,`deleted_at`),
  KEY `idx__asset_id__deleted_at` (`asset_id`,`deleted_at`),
  KEY `fk_document_entries_types1_idx` (`asset_type_id`),
  KEY `fk_document_entries_languages1_idx` (`language_id`),
  KEY `fk_document_entries_product_groups1_idx` (`product_group_id`),
  KEY `fk_document_entries_product_lines1_idx` (`product_line_id`),
  KEY `fk_document_entries_psps1_idx` (`psp_id`),
  CONSTRAINT `fk_document_entries_assets1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_currencies1` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_documents1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_languages1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_product_groups1` FOREIGN KEY (`product_group_id`) REFERENCES `product_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_product_lines1` FOREIGN KEY (`product_line_id`) REFERENCES `product_lines` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_products2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_psps1` FOREIGN KEY (`psp_id`) REFERENCES `psps` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_service_groups1` FOREIGN KEY (`service_group_id`) REFERENCES `service_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_service_levels1` FOREIGN KEY (`service_level_id`) REFERENCES `service_levels` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_entries_types1` FOREIGN KEY (`asset_type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_statuses` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `status_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_status` (`document_id`,`status_id`,`deleted_not`),
  KEY `fk_document_statuses_statuses1_idx` (`status_id`),
  KEY `fk_document_statuses_documents1_idx` (`document_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_document_statuses_documents1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_document_statuses_statuses1` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_said` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `distributor_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `number` varchar(64) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `price_origin` decimal(12,2) DEFAULT NULL,
  `currency_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `language_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_amp_id` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_sar_number` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `entries_count` int unsigned NOT NULL DEFAULT '0',
  `contacts_count` int unsigned NOT NULL DEFAULT '0',
  `statuses_count` int unsigned NOT NULL DEFAULT '0',
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_documents_types1_idx` (`type_id`),
  KEY `fk_documents_customers1_idx` (`customer_id`),
  KEY `fk_documents_oems1_idx` (`oem_id`),
  KEY `fk_documents_currencies1_idx` (`currency_id`),
  KEY `fk_documents_resellers1_idx` (`reseller_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_document_languages1_idx` (`language_id`),
  KEY `fk_document_distributors1_idx` (`distributor_id`),
  KEY `fk_documents_oem_groups1_idx` (`oem_group_id`),
  KEY `idx__number__type_id__deleted_at` (`number`,`type_id`,`deleted_at`),
  KEY `idx__statuses_count__type_id__deleted_at` (`statuses_count`,`type_id`,`deleted_at`) /*!80000 INVISIBLE */,
  KEY `idx__reseller_id__deleted_at` (`reseller_id`,`deleted_at`) /*!80000 INVISIBLE */,
  KEY `idx__reseller_id__type_id__deleted_at` (`reseller_id`,`type_id`,`deleted_at`),
  KEY `idx__statuses_count__deleted_at` (`statuses_count`,`deleted_at`),
  FULLTEXT KEY `ftx__number` (`number`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `fk_document_languages1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_currencies1` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_distributors1` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_oem_groups1` FOREIGN KEY (`oem_group_id`) REFERENCES `oem_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_documents_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `connection` text COLLATE utf8mb4_0900_as_ci NOT NULL,
  `queue` text COLLATE utf8mb4_0900_as_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_0900_as_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_0900_as_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `disk` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `size` int NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `hash` char(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__object_id__object_type__deleted_at` (`object_id`,`object_type`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `sender_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `role_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `team_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invitations_organizations1_idx` (`organization_id`),
  KEY `fk_invitations_users1_idx` (`user_id`),
  KEY `fk_invitations_roles1_idx` (`role_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_invitations_users2_idx` (`sender_id`),
  KEY `fk_invitations_teams1_idx` (`team_id`),
  CONSTRAINT `fk_invitations_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_roles1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_teams1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_users2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_0900_as_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_0900_as_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpis` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `assets_total` int unsigned NOT NULL DEFAULT '0',
  `assets_active` int unsigned NOT NULL DEFAULT '0',
  `assets_active_percent` double unsigned NOT NULL DEFAULT '0',
  `assets_active_on_contract` int unsigned NOT NULL DEFAULT '0',
  `assets_active_on_warranty` int unsigned NOT NULL DEFAULT '0',
  `assets_active_exposed` int unsigned NOT NULL DEFAULT '0',
  `customers_active` int unsigned NOT NULL DEFAULT '0',
  `customers_active_new` int unsigned NOT NULL DEFAULT '0',
  `contracts_active` int unsigned NOT NULL DEFAULT '0',
  `contracts_active_amount` double unsigned NOT NULL DEFAULT '0',
  `contracts_active_new` int unsigned NOT NULL DEFAULT '0',
  `contracts_expiring` int unsigned NOT NULL DEFAULT '0',
  `contracts_expired` int unsigned NOT NULL DEFAULT '0',
  `quotes_active` int unsigned NOT NULL DEFAULT '0',
  `quotes_active_amount` double unsigned NOT NULL DEFAULT '0',
  `quotes_active_new` int unsigned NOT NULL DEFAULT '0',
  `quotes_expiring` int unsigned NOT NULL DEFAULT '0',
  `quotes_expired` int unsigned NOT NULL DEFAULT '0',
  `quotes_ordered` int unsigned NOT NULL DEFAULT '0',
  `quotes_accepted` int unsigned NOT NULL DEFAULT '0',
  `quotes_requested` int unsigned NOT NULL DEFAULT '0',
  `quotes_received` int unsigned NOT NULL DEFAULT '0',
  `quotes_rejected` int unsigned NOT NULL DEFAULT '0',
  `quotes_awaiting` int unsigned NOT NULL DEFAULT '0',
  `service_revenue_total_amount` double unsigned NOT NULL DEFAULT '0',
  `service_revenue_total_amount_change` double NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `code` char(2) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__code__deleted_not` (`code`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_customers` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__customer` (`location_id`,`customer_id`,`deleted_not`),
  KEY `fk_location_customers_locations1_idx` (`location_id`),
  KEY `fk_location_customers_customers1_idx` (`customer_id`),
  KEY `idx__deleted_at` (`deleted_at`) /*!80000 INVISIBLE */,
  CONSTRAINT `fk_location_customers_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_location_customers_locations1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_resellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_resellers` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unuqie__reseller` (`location_id`,`reseller_id`,`deleted_not`),
  KEY `fk_location_resellers_resellers1_idx` (`reseller_id`),
  KEY `fk_location_resellers_locations1_idx` (`location_id`) /*!80000 INVISIBLE */,
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_location_resellers_locations1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_location_resellers_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `country_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `city_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `postcode` varchar(45) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `line_one` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `line_two` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geohash` varchar(12) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `customers_count` int unsigned NOT NULL DEFAULT '0',
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__location` (`country_id`,`city_id`,`postcode`,`line_one`,`line_two`),
  KEY `fk_locations_countries1_idx` (`country_id`),
  KEY `fk_locations_cities1_idx` (`city_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__postcode__deleted_at` (`postcode`,`deleted_at`),
  KEY `idx__latitude__longitude__deleted_at` (`latitude`,`longitude`,`deleted_at`),
  KEY `idx__longitude__latitude__deleted_at` (`longitude`,`latitude`,`deleted_at`),
  CONSTRAINT `fk_locations_cities100` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_locations_countries100` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `category` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `parent_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `index` int unsigned NOT NULL DEFAULT '0',
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `object_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `duration` double unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` timestamp NULL DEFAULT NULL,
  `statistics` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_logs_logs1_idx` (`parent_id`),
  KEY `idx__action__category` (`category`,`action`),
  KEY `idx__object_id__object_type` (`object_id`,`object_type`) /*!80000 INVISIBLE */,
  KEY `idx__category__action__object_type__created_at` (`category`,`action`,`object_type`,`created_at`),
  KEY `idx__category__action__status__object_type__created_at` (`category`,`action`,`status`,`object_type`,`created_at`),
  CONSTRAINT `fk_logs_logs1` FOREIGN KEY (`parent_id`) REFERENCES `logs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `change_request_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `quote_request_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_0900_as_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  KEY `fk_notes_users1_idx` (`user_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_notes_documents1_idx` (`document_id`),
  KEY `fk_notes_organizations1_idx` (`organization_id`),
  KEY `fk_notes_change_requests1_idx` (`change_request_id`),
  KEY `fk_notes_quote_requests1_idx` (`quote_request_id`),
  CONSTRAINT `fk_notes_change_requests1` FOREIGN KEY (`change_request_id`) REFERENCES `change_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_notes_documents1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_notes_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_notes_quote_requests1` FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_notes_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oem_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oem_groups` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__name__oem_id__deleted_not` (`key`,`name`,`oem_id`,`deleted_not`),
  KEY `fk_oem_groups_oems1_idx` (`oem_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_oem_groups_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oems` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(32) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organization_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_users` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `role_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `team_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `invited` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__organization_id__user_id__deleted_not` (`organization_id`,`user_id`,`deleted_not`),
  KEY `fk_organization_users_users1_idx` (`user_id`),
  KEY `fk_organization_users_organizations1_idx` (`organization_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_organization_users_teams1_idx` (`team_id`),
  KEY `fk_organization_users_roles1_idx` (`role_id`),
  CONSTRAINT `fk_organization_users_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_organization_users_roles1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_organization_users_teams1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_organization_users_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `keycloak_name` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `keycloak_scope` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `keycloak_group_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `locale` varchar(8) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `currency_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `website_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `analytics_code` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_dark_theme` tinyint(1) DEFAULT NULL,
  `branding_main_color` varchar(7) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_secondary_color` varchar(7) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_logo_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_favicon_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_default_main_color` varchar(7) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_default_secondary_color` varchar(7) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_default_logo_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_default_favicon_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_welcome_image_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `branding_welcome_heading` json DEFAULT NULL,
  `branding_welcome_underline` json DEFAULT NULL,
  `branding_dashboard_image_url` varchar(2048) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__keycloak_group_id__deleted_not` (`keycloak_group_id`,`deleted_not`),
  UNIQUE KEY `unique__keycloak_scope__deleted_not` (`keycloak_scope`,`deleted_not`),
  UNIQUE KEY `unique__keycloak_name__deleted_not` (`keycloak_name`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_organizations_currencies1_idx` (`currency_id`),
  KEY `idx__name__deleted_at` (`name`,`deleted_at`),
  CONSTRAINT `fk_organizations_currencies1` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`id`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_groups` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_lines` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `eol` timestamp NULL DEFAULT NULL,
  `eos` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__sku__oem_id__deleted_not` (`sku`,`oem_id`,`deleted_not`),
  KEY `fk_products_oems1_idx` (`oem_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__sku__name__deleted_at` (`sku`,`name`,`deleted_at`),
  KEY `idx__name__deleted_at` (`name`,`deleted_at`),
  FULLTEXT KEY `ftx__name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `fk_products_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `psps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `psps` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_request_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_request_assets` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `request_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `service_level_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `service_level_custom` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `duration_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quote_request_assets_quote_requests1_idx` (`request_id`),
  KEY `fk_quote_request_assets_assets1_idx` (`asset_id`),
  KEY `fk_quote_request_assets_service_levels1_idx` (`service_level_id`),
  KEY `fk_quote_request_assets_durations1_idx` (`duration_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quote_request_assets_assets1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_assets_quote_request_durations1` FOREIGN KEY (`duration_id`) REFERENCES `quote_request_durations` (`id`),
  CONSTRAINT `fk_quote_request_assets_quote_requests1` FOREIGN KEY (`request_id`) REFERENCES `quote_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_assets_service_levels1` FOREIGN KEY (`service_level_id`) REFERENCES `service_levels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_request_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_request_documents` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `request_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `duration_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quote_request_documents_quote_requests1_idx` (`request_id`),
  KEY `fk_quote_request_documents_quote_request_durations1_idx` (`duration_id`),
  KEY `fk_quote_request_documents_documents1_idx` (`document_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quote_request_documents_documents1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_documents_quote_request_durations1` FOREIGN KEY (`duration_id`) REFERENCES `quote_request_durations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_documents_quote_requests1` FOREIGN KEY (`request_id`) REFERENCES `quote_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_request_durations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_request_durations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_requests` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_custom` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_copy` tinyint(1) NOT NULL DEFAULT '0',
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `customer_custom` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `type_custom` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_0900_as_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quote_requests_oems1_idx` (`oem_id`),
  KEY `fk_quote_requests_organizations1_idx` (`organization_id`),
  KEY `fk_quote_requests_users1_idx` (`user_id`),
  KEY `fk_quote_requests_customers1_idx` (`customer_id`),
  KEY `fk_quote_requests_types1_idx` (`type_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quote_requests_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_customers` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customer_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `kpi_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `quotes_count` int unsigned NOT NULL DEFAULT '0',
  `contracts_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__reseller_id__customer_id__deleted_not` (`reseller_id`,`customer_id`,`deleted_not`),
  KEY `fk_reseller_customers_customers1_idx` (`customer_id`),
  KEY `fk_reseller_customers_resellers1_idx` (`reseller_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `fk_reseller_customers_kpis1_idx` (`kpi_id`),
  CONSTRAINT `fk_reseller_customers_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_reseller_customers_kpis1` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`),
  CONSTRAINT `fk_reseller_customers_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_location_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_location_types` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reseller_location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `type_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__type` (`reseller_location_id`,`type_id`,`deleted_not`),
  KEY `fk_reseller_location_types_types1_idx` (`type_id`),
  KEY `fk_reseller_location_types_reseller_locations1_idx` (`reseller_location_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_reseller_location_types_reseller_locations1` FOREIGN KEY (`reseller_location_id`) REFERENCES `reseller_locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_reseller_location_types_types1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_locations` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `location_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `customers_count` int unsigned NOT NULL DEFAULT '0',
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__location` (`reseller_id`,`location_id`,`deleted_not`),
  KEY `fk_reseller_locations_locations1_idx` (`location_id`),
  KEY `fk_reseller_locations_resellers1_idx` (`reseller_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_reseller_locations_locations1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_reseller_locations_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_statuses` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reseller_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `status_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__reseller_id__status_id__deleted_not` (`reseller_id`,`status_id`,`deleted_not`),
  KEY `fk_reseller_statuses_statuses1_idx` (`status_id`),
  KEY `fk_reseller_statuses_resellers1_idx` (`reseller_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_reseller_statuses_resellers1` FOREIGN KEY (`reseller_id`) REFERENCES `resellers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_reseller_statuses_statuses1` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resellers` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `kpi_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `customers_count` int unsigned NOT NULL DEFAULT '0',
  `locations_count` int unsigned NOT NULL DEFAULT '0',
  `assets_count` int unsigned NOT NULL DEFAULT '0',
  `contacts_count` int unsigned NOT NULL DEFAULT '0',
  `statuses_count` int unsigned NOT NULL DEFAULT '0',
  `hash` char(40) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx__deleted_at` (`deleted_at`),
  KEY `idx__name__deleted_at` (`name`,`deleted_at`),
  KEY `idx__statuses_count__deleted_at` (`statuses_count`,`deleted_at`),
  KEY `fk_resellers_kpis1_idx` (`kpi_id`),
  CONSTRAINT `fk_resellers_kpis1` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `role_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `permission_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__role_id__permission_id__deleted_not` (`role_id`,`permission_id`,`deleted_not`),
  KEY `fk_role_permissions_roles1_idx` (`role_id`),
  KEY `fk_role_permissions_permissions1_idx` (`permission_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_role_permissions_permissions1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_role_permissions_roles1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  `_organization_id` char(36) COLLATE utf8mb4_0900_as_ci GENERATED ALWAYS AS (ifnull(`organization_id`,_utf8mb4'00000000-0000-0000-0000-000000000000')) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__name__organization_id__deleted_not` (`name`,`_organization_id`,`deleted_not`) /*!80000 INVISIBLE */,
  KEY `fk_roles_organizations1_idx` (`organization_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_roles_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_groups` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__sku__oem_id__deleted_not` (`sku`,`oem_id`,`deleted_not`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `fk_service_groups_oems1_idx` (`oem_id`) /*!80000 INVISIBLE */,
  CONSTRAINT `fk_service_groups_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_levels` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `oem_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `service_group_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__sku__oem_id__oem_service_group_id__deleted_not` (`sku`,`oem_id`,`service_group_id`,`deleted_not`),
  UNIQUE KEY `unique__key__deleted_not` (`key`,`deleted_not`),
  KEY `fk_service_levels_oems1_idx` (`oem_id`),
  KEY `fk_service_levels_oem_service_groups1_idx` (`service_group_id`),
  CONSTRAINT `fk_service_levels_oem_service_groups1` FOREIGN KEY (`service_group_id`) REFERENCES `service_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_service_levels_oems1` FOREIGN KEY (`oem_id`) REFERENCES `oems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuses` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__object_type__key__deleted_not` (`object_type`,`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__name__deleted_not` (`name`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__name__deleted_not` (`name`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `testing__search__fulltext_processors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testing__search__fulltext_processors` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(45) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `description` text COLLATE utf8mb4_0900_as_ci,
  PRIMARY KEY (`id`),
  KEY `idx__name` (`name`) /*!80000 INVISIBLE */,
  FULLTEXT KEY `ftx__name` (`name`) /*!80000 INVISIBLE */,
  FULLTEXT KEY `ftx__description` (`description`) /*!80000 INVISIBLE */ /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ftx__name__description` (`description`,`name`) /*!50100 WITH PARSER `ngram` */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `types` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `object_type` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(64) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__object_type__key__deleted_not` (`object_type`,`key`,`deleted_not`),
  KEY `idx__deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_searches` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `conditions` text COLLATE utf8mb4_0900_as_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_searches_users1_idx` (`user_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_user_searches_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(36) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `type` enum('local','keycloak') COLLATE utf8mb4_0900_as_ci NOT NULL DEFAULT 'keycloak',
  `organization_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `given_name` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `family_name` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_0900_as_ci NOT NULL,
  `email_verified` tinyint(1) NOT NULL,
  `phone` varchar(16) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `phone_verified` tinyint(1) DEFAULT NULL,
  `photo` varchar(1024) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `permissions` json NOT NULL,
  `locale` varchar(8) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `homepage` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `office_phone` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `title` varchar(7) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `academic_title` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `mobile_phone` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `previous_sign_in` timestamp NULL DEFAULT NULL,
  `freshchat_id` char(36) COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_not` tinyint(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique__email__deleted_not` (`email`,`deleted_not`),
  KEY `fk_users_organizations1_idx` (`organization_id`),
  KEY `idx__deleted_at` (`deleted_at`),
  CONSTRAINT `fk_users_organizations1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (2,'2021_02_08_072527_create_laravel_job_batches_table',1);
INSERT INTO `migrations` VALUES (3,'2021_02_11_084351_create_organizations',1);
INSERT INTO `migrations` VALUES (4,'2021_02_12_081404_create_users',1);
INSERT INTO `migrations` VALUES (5,'2021_02_23_063659_create_oems_table',1);
INSERT INTO `migrations` VALUES (6,'2021_02_23_063724_create_products_tables',1);
INSERT INTO `migrations` VALUES (7,'2021_02_24_114717_create_countries_table',1);
INSERT INTO `migrations` VALUES (8,'2021_02_24_131908_create_cities_table',1);
INSERT INTO `migrations` VALUES (9,'2021_02_25_060132_create_locations_table',1);
INSERT INTO `migrations` VALUES (10,'2021_02_26_110108_create_customer_location_types_table',1);
INSERT INTO `migrations` VALUES (11,'2021_02_26_114948_rename_customer_location_types_to_types',1);
INSERT INTO `migrations` VALUES (12,'2021_02_26_133731_create_statuses_table',1);
INSERT INTO `migrations` VALUES (13,'2021_02_26_135227_create_customers_table',1);
INSERT INTO `migrations` VALUES (14,'2021_03_01_064641_create_customer_locations_table',1);
INSERT INTO `migrations` VALUES (15,'2021_03_03_080727_alter_table_countries_code2',1);
INSERT INTO `migrations` VALUES (16,'2021_03_03_084827_create_contacts_table',1);
INSERT INTO `migrations` VALUES (17,'2021_03_03_111352_create_contact_types_table',1);
INSERT INTO `migrations` VALUES (18,'2021_03_03_152502_change_structure_of_customer_locations',1);
INSERT INTO `migrations` VALUES (19,'2021_03_04_122213_drop_table_product_categories',1);
INSERT INTO `migrations` VALUES (20,'2021_03_04_124851_alter_products_table_add_type_eol_oef',1);
INSERT INTO `migrations` VALUES (21,'2021_03_04_185844_alter_table_customers',1);
INSERT INTO `migrations` VALUES (22,'2021_03_05_071336_create_assets_table',1);
INSERT INTO `migrations` VALUES (23,'2021_03_05_110848_move_products_type_to_assets',1);
INSERT INTO `migrations` VALUES (24,'2021_03_09_113917_alter_table_locations_make_object_id_nullable',1);
INSERT INTO `migrations` VALUES (25,'2021_03_09_192028_alter_customers_table_add_contacts_count_field',1);
INSERT INTO `migrations` VALUES (26,'2021_03_10_115710_alter_table_organizations_add_countable_remove_abbr_and_type',1);
INSERT INTO `migrations` VALUES (27,'2021_03_11_132556_alter_table_assets_add_organization',1);
INSERT INTO `migrations` VALUES (28,'2021_03_12_102710_alter_table_contact_adjust_phone_number_length',1);
INSERT INTO `migrations` VALUES (29,'2021_03_15_141834_alter_table_contacts_phone_canbe_related_to_mutiple_names',1);
INSERT INTO `migrations` VALUES (30,'2021_03_16_193201_alter_users_table_add_locale_field',1);
INSERT INTO `migrations` VALUES (31,'2021_03_18_071846_foreign_constraint_restriction_fix',1);
INSERT INTO `migrations` VALUES (32,'2021_03_18_132839_extract_resellers_from_organization',1);
INSERT INTO `migrations` VALUES (33,'2021_03_19_064821_create_currencies_table',1);
INSERT INTO `migrations` VALUES (34,'2021_03_19_072811_alter_table_products_add_type',1);
INSERT INTO `migrations` VALUES (35,'2021_03_19_115757_create_documents_tables',1);
INSERT INTO `migrations` VALUES (36,'2021_03_19_134435_create_asset_warranties_table',1);
INSERT INTO `migrations` VALUES (37,'2021_03_22_062620_add_support_level_to_documents',1);
INSERT INTO `migrations` VALUES (38,'2021_03_23_130511_alter_asset_warranties_make_document_id_nullable',1);
INSERT INTO `migrations` VALUES (39,'2021_03_25_072257_create_table_reseller_customers',1);
INSERT INTO `migrations` VALUES (40,'2021_03_26_190219_alter_table_organization_add_locale_currency_theme_properties',1);
INSERT INTO `migrations` VALUES (41,'2021_03_30_062119_alter_asset_warranties_end_notnull_document_entries_remove_oem',1);
INSERT INTO `migrations` VALUES (42,'2021_03_30_071751_alter_assets_serial_number_can_be_null',1);
INSERT INTO `migrations` VALUES (43,'2021_03_31_060527_create_asset_warranty_products',1);
INSERT INTO `migrations` VALUES (44,'2021_03_31_124509_document_asset_warranties_start_end_converted_into_date',1);
INSERT INTO `migrations` VALUES (45,'2021_04_04_215616_alter_table_organization_add_webiste_url_email_fields',1);
INSERT INTO `migrations` VALUES (46,'2021_04_16_105424_add_deleted_at_indexes',1);
INSERT INTO `migrations` VALUES (47,'2021_04_16_133515_alter_documents_table_price_can_be_null',1);
INSERT INTO `migrations` VALUES (48,'2021_04_19_061647_alter_locations_lat_lng_to_latitude_longitude',1);
INSERT INTO `migrations` VALUES (49,'2021_04_19_125852_fix_contacts_unique_indexes',1);
INSERT INTO `migrations` VALUES (50,'2021_04_19_203849_create_user_searches_table',1);
INSERT INTO `migrations` VALUES (51,'2021_04_20_063115_alter_users_remove_sub_column',1);
INSERT INTO `migrations` VALUES (52,'2021_04_22_123559_alter_users_drop_block_change_verified_to_bool',1);
INSERT INTO `migrations` VALUES (53,'2021_04_26_081617_alter_table_document_entries_add_price_currency',1);
INSERT INTO `migrations` VALUES (54,'2021_04_26_134637_alter_documents_currency_id_can_be_null',1);
INSERT INTO `migrations` VALUES (55,'2021_04_29_084137_create_languages_table',1);
INSERT INTO `migrations` VALUES (56,'2021_04_29_215057_alter_table_organization_make_branding_dark_nullable',1);
INSERT INTO `migrations` VALUES (57,'2021_05_04_074551_database_schema_fixes',1);
INSERT INTO `migrations` VALUES (58,'2021_05_05_105042_alter_table_organizations_add_keycloak_scope',1);
INSERT INTO `migrations` VALUES (59,'2021_05_05_222730_alter_table_documents_add_language_id_column',1);
INSERT INTO `migrations` VALUES (60,'2021_05_06_124023_alter_table_users_organization_id_can_be_null',1);
INSERT INTO `migrations` VALUES (61,'2021_05_07_132440_alter_assets_table_add_status_id',1);
INSERT INTO `migrations` VALUES (62,'2021_05_08_110949_alter_assets_table_add_contacts_count',1);
INSERT INTO `migrations` VALUES (63,'2021_05_11_124429_alter_table_users_add_type',1);
INSERT INTO `migrations` VALUES (64,'2021_05_11_213329_alter_table_documents_add_contacts_count',1);
INSERT INTO `migrations` VALUES (65,'2021_05_12_075450_alter_table_users_add_password',1);
INSERT INTO `migrations` VALUES (66,'2021_05_12_123143_create_asset_coverage_table',1);
INSERT INTO `migrations` VALUES (67,'2021_05_12_131233_create_password_resets_table',1);
INSERT INTO `migrations` VALUES (68,'2021_05_13_065613_alter_table_organizations_remove_subdomain',1);
INSERT INTO `migrations` VALUES (69,'2021_05_13_142011_alter_table_documents_add_estimated_value_renewal',1);
INSERT INTO `migrations` VALUES (70,'2021_05_14_065129_alter_table_organizations_keycloak_scope_should_be_unique',1);
INSERT INTO `migrations` VALUES (71,'2021_05_17_110137_move_estimated_value_renewal_into_documents_entries',1);
INSERT INTO `migrations` VALUES (72,'2021_05_17_125327_alter_table_documents_product_id_can_be_null',1);
INSERT INTO `migrations` VALUES (73,'2021_05_17_210716_add_email_to_contacts_unique_index',1);
INSERT INTO `migrations` VALUES (74,'2021_05_18_221358_create_distributors_table',1);
INSERT INTO `migrations` VALUES (75,'2021_05_19_212804_alter_organization_add_key_clock_group_id',1);
INSERT INTO `migrations` VALUES (76,'2021_05_20_052716_alter_documents_table_add_entries_count',1);
INSERT INTO `migrations` VALUES (77,'2021_05_20_053620_alter_table_document_entries_remove_quantity',1);
INSERT INTO `migrations` VALUES (78,'2021_05_20_132326_alter_table_documents_reseller_id_can_be_null',1);
INSERT INTO `migrations` VALUES (79,'2021_05_21_101927_alter_table_document_entries_rename_product_to_service',1);
INSERT INTO `migrations` VALUES (80,'2021_05_24_093700_alter_table_documents_rename_product_to_support',1);
INSERT INTO `migrations` VALUES (81,'2021_05_24_113002_rename_table_asset_warranty_products_to_asset_warranty_services',1);
INSERT INTO `migrations` VALUES (82,'2021_05_24_125438_alter_table_document_entries_add_product_id_and_serial_number',1);
INSERT INTO `migrations` VALUES (83,'2021_05_25_003938_alter_users_table_phone_fields_nullable',1);
INSERT INTO `migrations` VALUES (84,'2021_05_25_094403_alter_table_organizations_add_unique__keycloak_scope__deleted_at',1);
INSERT INTO `migrations` VALUES (85,'2021_05_25_210556_create_tags_table',1);
INSERT INTO `migrations` VALUES (86,'2021_05_26_054158_alter_organizations_branding',1);
INSERT INTO `migrations` VALUES (87,'2021_05_27_212940_create_asset_tags_table',1);
INSERT INTO `migrations` VALUES (88,'2021_05_29_150809_alter_table_assets_add_data_quality_score',1);
INSERT INTO `migrations` VALUES (89,'2021_05_29_210141_alter_resellers_add_status_id_type_id',1);
INSERT INTO `migrations` VALUES (90,'2021_05_31_215643_alter_table_resellers_add_contacts_count',1);
INSERT INTO `migrations` VALUES (91,'2021_06_01_062352_create_logs_tables',1);
INSERT INTO `migrations` VALUES (92,'2021_06_01_214600_alter_organizations_add_timezone',1);
INSERT INTO `migrations` VALUES (93,'2021_06_04_072222_alter_table_logs_no_enums_new_indexes',1);
INSERT INTO `migrations` VALUES (94,'2021_06_09_073729_indexes_fix',1);
INSERT INTO `migrations` VALUES (95,'2021_06_09_081835_unique_indexes_fix',1);
INSERT INTO `migrations` VALUES (96,'2021_06_09_093428_reseller_customers_fix',1);
INSERT INTO `migrations` VALUES (97,'2021_06_11_052347_alter_documents_start_end_nullable',1);
INSERT INTO `migrations` VALUES (98,'2021_06_11_120416_alter_tables_expand_columns',1);
INSERT INTO `migrations` VALUES (99,'2021_06_11_211913_create_roles_table',1);
INSERT INTO `migrations` VALUES (100,'2021_06_14_054734_create_analyze_assets',1);
INSERT INTO `migrations` VALUES (101,'2021_06_15_225353_create_permissions_table',1);
INSERT INTO `migrations` VALUES (102,'2021_06_18_051915_alter_table_document_entries_service_nullable',1);
INSERT INTO `migrations` VALUES (103,'2021_06_19_114345_alter_roles_table_make_name_unique',1);
INSERT INTO `migrations` VALUES (104,'2021_06_21_003916_alter_table_permissions_remove_keycloak_fields',1);
INSERT INTO `migrations` VALUES (105,'2021_06_23_052602_alter_table_asset_warranties_customer_nullable',1);
INSERT INTO `migrations` VALUES (106,'2021_06_23_075706_alter_table_asset_warranties_add_support_id',1);
INSERT INTO `migrations` VALUES (107,'2021_06_24_051932_alter_table_asset_warranties_update_unique_index',1);
INSERT INTO `migrations` VALUES (108,'2021_06_24_065212_alter_table_assets_type_nullable',1);
INSERT INTO `migrations` VALUES (109,'2021_07_01_134852_alter_logs_convert_duration_into_double',1);
INSERT INTO `migrations` VALUES (110,'2021_07_05_082716_alter_table_locations_add_index_by_coordinate',1);
INSERT INTO `migrations` VALUES (111,'2021_07_05_123014_alter_table_asset_warranties_add_index_by_end',1);
INSERT INTO `migrations` VALUES (112,'2021_07_09_080712_rename_asset_coverages_into_coverages',1);
INSERT INTO `migrations` VALUES (113,'2021_07_09_102752_create_table_assets_coverages',1);
INSERT INTO `migrations` VALUES (114,'2021_07_11_000343_create_notes_table',1);
INSERT INTO `migrations` VALUES (115,'2021_07_12_111536_alter_customers_reseller_multiple_status',1);
INSERT INTO `migrations` VALUES (116,'2021_07_13_131430_alter_table_resellers_type_id_not_null',1);
INSERT INTO `migrations` VALUES (117,'2021_07_13_213518_create_files_table',1);
INSERT INTO `migrations` VALUES (118,'2021_07_14_065529_alter_table_documents_add_assets_count',1);
INSERT INTO `migrations` VALUES (119,'2021_07_14_075511_create_oem_groups',1);
INSERT INTO `migrations` VALUES (120,'2021_07_15_051230_alter_data_loader_tables_changed_at_support',1);
INSERT INTO `migrations` VALUES (121,'2021_07_15_101834_search_indexes',1);
INSERT INTO `migrations` VALUES (122,'2021_07_15_105426_search_indexes_improvements',1);
INSERT INTO `migrations` VALUES (123,'2021_07_16_061951_service_groups_and_levels',1);
INSERT INTO `migrations` VALUES (124,'2021_07_19_133626_rename_asset_warranty_services_to_service_levels',1);
INSERT INTO `migrations` VALUES (125,'2021_07_19_220412_alter_users_table_add_portal_settings_field',1);
INSERT INTO `migrations` VALUES (126,'2021_07_20_111042_alter_table_oems_rename_abbr_into_key',1);
INSERT INTO `migrations` VALUES (127,'2021_07_21_110336_alter_notes_table_add_pinned',1);
INSERT INTO `migrations` VALUES (128,'2021_07_21_141924_create_change_request_table',1);
INSERT INTO `migrations` VALUES (129,'2021_07_22_064250_document_entries_add_service_group_id',1);
INSERT INTO `migrations` VALUES (130,'2021_07_22_131758_alter_files_add_object_id_type',1);
INSERT INTO `migrations` VALUES (131,'2021_07_26_140936_change_requests_object_fix',1);
INSERT INTO `migrations` VALUES (132,'2021_07_30_183348_create_quote_request_durations_table',1);
INSERT INTO `migrations` VALUES (133,'2021_07_30_193503_create_quote_requests_table',1);
INSERT INTO `migrations` VALUES (134,'2021_07_30_193704_create_quote_request_assets_table',1);
INSERT INTO `migrations` VALUES (135,'2021_08_03_075958_quote_request_durations_seed',1);
INSERT INTO `migrations` VALUES (136,'2021_08_06_164048_create_audits_table',1);
INSERT INTO `migrations` VALUES (137,'2021_08_13_124126_alter_table_logs_index_int',1);
INSERT INTO `migrations` VALUES (138,'2021_08_16_063756_logs_add_indexes',1);
INSERT INTO `migrations` VALUES (139,'2021_08_20_004821_create_organization_users_table',1);
INSERT INTO `migrations` VALUES (140,'2021_08_20_112610_create_user_roles_table',1);
INSERT INTO `migrations` VALUES (141,'2021_08_26_133354_add_orgnization_kpis',1);
INSERT INTO `migrations` VALUES (142,'2021_08_27_062026_customers_add_kpi',1);
INSERT INTO `migrations` VALUES (143,'2021_09_01_223041_alter_users_table_add_enabled',1);
INSERT INTO `migrations` VALUES (144,'2021_09_02_205206_alter_roles_table_change_unique_index',1);
INSERT INTO `migrations` VALUES (145,'2021_09_03_141011_alter_users_table_names_nullable',1);
INSERT INTO `migrations` VALUES (146,'2021_09_05_190427_alter_users_add_profile_fields',1);
INSERT INTO `migrations` VALUES (147,'2021_09_06_220423_create_invitations_table',1);
INSERT INTO `migrations` VALUES (148,'2021_09_07_083313_alter_users_enable_local',1);
INSERT INTO `migrations` VALUES (149,'2021_09_07_095202_sync_keycloak_users',1);
INSERT INTO `migrations` VALUES (150,'2021_09_09_131228_invitations_rename_created_by_to_sender_id',1);
INSERT INTO `migrations` VALUES (151,'2021_09_09_222132_alter_invitations_table_add_expired_at',1);
INSERT INTO `migrations` VALUES (152,'2021_09_09_233338_create_role_permissions_table',1);
INSERT INTO `migrations` VALUES (153,'2021_09_14_204001_alter_quote_request_add_customer_name',1);
INSERT INTO `migrations` VALUES (154,'2021_09_17_105306_roles_organization_id_nullable',1);
INSERT INTO `migrations` VALUES (155,'2021_09_17_122443_create_teams_table',1);
INSERT INTO `migrations` VALUES (156,'2021_09_20_065427_kpi_to_separate_table',1);
INSERT INTO `migrations` VALUES (157,'2021_09_20_133313_kpis_more_kpis',1);
INSERT INTO `migrations` VALUES (158,'2021_09_20_214825_teams_seed',1);
INSERT INTO `migrations` VALUES (159,'2021_09_21_213926_alter_organization_users_table_add_role_id',1);
INSERT INTO `migrations` VALUES (160,'2021_09_22_074927_locations_add_assets_count',1);
INSERT INTO `migrations` VALUES (161,'2021_09_22_134559_documents_customer_id_nullable',1);
INSERT INTO `migrations` VALUES (162,'2021_09_23_060444_locations_structure_update',1);
INSERT INTO `migrations` VALUES (163,'2021_09_23_075852_locations_structure_update_convert_data',1);
INSERT INTO `migrations` VALUES (164,'2021_09_23_125052_locations_structure_update_finish',1);
INSERT INTO `migrations` VALUES (165,'2021_09_27_134708_location_resellers_create',1);
INSERT INTO `migrations` VALUES (166,'2021_09_27_211555_alter_organizations_branding_add_image_url',1);
INSERT INTO `migrations` VALUES (167,'2021_09_28_054114_location_customers_create',1);
INSERT INTO `migrations` VALUES (168,'2021_09_28_082950_reseller_customers_countable',1);
INSERT INTO `migrations` VALUES (169,'2021_09_29_212539_alter_organization_users_add_enabled',1);
INSERT INTO `migrations` VALUES (170,'2021_09_30_051826_locations_structure_update_recalculate',1);
INSERT INTO `migrations` VALUES (171,'2021_09_30_124955_document_status_create',1);
INSERT INTO `migrations` VALUES (172,'2021_09_30_141945_documents_remove_service_group_id',1);
INSERT INTO `migrations` VALUES (173,'2021_10_08_063326_assets_add_warranty_end',1);
INSERT INTO `migrations` VALUES (174,'2021_10_08_095341_assets_better_indexes',1);
INSERT INTO `migrations` VALUES (175,'2021_10_11_104656_assets_documents_fulltext_indexes',1);
INSERT INTO `migrations` VALUES (176,'2021_10_12_064739_customers_products_fulltext_indexes',1);
INSERT INTO `migrations` VALUES (177,'2021_10_13_070805_locations_geohash',1);
INSERT INTO `migrations` VALUES (178,'2021_10_14_055210_document_entries_asset_id_deleted_at_index',1);
INSERT INTO `migrations` VALUES (179,'2021_10_15_122534_object_synched_at',1);
INSERT INTO `migrations` VALUES (180,'2021_10_19_081322_sync-permissions',1);
INSERT INTO `migrations` VALUES (181,'2021_11_02_114848_synced_at_incorrect_datetime_value_fix',1);
INSERT INTO `migrations` VALUES (182,'2021_11_02_124001_assets_add_coverages_count',1);
INSERT INTO `migrations` VALUES (183,'2021_11_05_103139_statuses_count_and_indexes',1);
INSERT INTO `migrations` VALUES (184,'2021_11_09_071901_asset_warranties_update',1);
INSERT INTO `migrations` VALUES (185,'2021_11_26_131738_location_resellers_drop_customers_count',1);
INSERT INTO `migrations` VALUES (186,'2021_12_02_060616_organization_users_default_value_for_enabled',1);
INSERT INTO `migrations` VALUES (187,'2021_12_13_062456_reseller_customers_kpi',1);
INSERT INTO `migrations` VALUES (188,'2021_12_14_135432_kpis_remove_object_type_and_object_id',1);
INSERT INTO `migrations` VALUES (189,'2021_12_23_150812_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (190,'2022_01_12_115356_users_remove_department',1);
INSERT INTO `migrations` VALUES (191,'2022_01_17_050420_invitations_expired_at_fix',1);
INSERT INTO `migrations` VALUES (192,'2022_01_17_054536_invitations_add_team_id',1);
INSERT INTO `migrations` VALUES (193,'2022_01_17_095439_organization_users_add_invited',1);
INSERT INTO `migrations` VALUES (194,'2022_01_18_052226_organization_users_fix_enabled',1);
INSERT INTO `migrations` VALUES (195,'2022_01_20_104745_sync_permissions',1);
INSERT INTO `migrations` VALUES (196,'2022_01_21_072701_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (197,'2022_01_31_070524_users_add_synced_at',1);
INSERT INTO `migrations` VALUES (198,'2022_02_24_065303_document_entries_add_start_end',1);
INSERT INTO `migrations` VALUES (199,'2022_03_01_142619_cities_service_groups_levels_add_key',1);
INSERT INTO `migrations` VALUES (200,'2022_03_02_072121_cities_service_groups_levels_update_key',1);
INSERT INTO `migrations` VALUES (201,'2022_03_02_072223_cities_service_groups_levels_index_key',1);
INSERT INTO `migrations` VALUES (202,'2022_03_02_110511_coverages_types_statuses_convert_name',1);
INSERT INTO `migrations` VALUES (203,'2022_03_08_071215_kpis_unsigned_fix',1);
INSERT INTO `migrations` VALUES (204,'2022_03_10_133319_organizations_branging_welcome_translations',1);
INSERT INTO `migrations` VALUES (205,'2022_03_23_125529_organizations_add_type',1);
INSERT INTO `migrations` VALUES (206,'2022_03_30_065715_organizations_change_type_to_varchar',1);
INSERT INTO `migrations` VALUES (207,'2022_04_14_071735_quote_request_documents_create',1);
INSERT INTO `migrations` VALUES (208,'2022_04_15_062959_quote_requests_custom_values',1);
INSERT INTO `migrations` VALUES (209,'2022_04_18_042439_assets_add_nickname',1);
INSERT INTO `migrations` VALUES (210,'2022_04_18_050027_sync_permissions',1);
INSERT INTO `migrations` VALUES (211,'2022_04_21_110649_asset_coverages_drop_indexes_by_asset_id_and_coverage_id',1);
INSERT INTO `migrations` VALUES (212,'2022_04_22_113626_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (213,'2022_04_26_124510_quote_requests_add_user_copy',1);
INSERT INTO `migrations` VALUES (214,'2022_05_20_042435_search__fulltext_processors',1);
INSERT INTO `migrations` VALUES (215,'2022_05_20_101117_assets_fix_idx__serial_number',1);
INSERT INTO `migrations` VALUES (216,'2022_05_26_103248_assets_product_id_nullable',1);
INSERT INTO `migrations` VALUES (217,'2022_05_31_144829_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (218,'2022_06_06_054812_assets_oem_id_nullable',1);
INSERT INTO `migrations` VALUES (219,'2022_06_06_070635_documents_oem_id_nullable',1);
INSERT INTO `migrations` VALUES (220,'2022_06_06_075453_documents_type_id_number_nullable',1);
INSERT INTO `migrations` VALUES (221,'2022_06_06_105849_document_entries_asset_id_nullable',1);
INSERT INTO `migrations` VALUES (222,'2022_06_07_120949_coverages_types_statuses_convert_name',1);
INSERT INTO `migrations` VALUES (223,'2022_06_10_055807_resellers_customers_remove_type_id',1);
INSERT INTO `migrations` VALUES (224,'2022_06_15_045412_reseller_customers_delete_locations_count',1);
INSERT INTO `migrations` VALUES (225,'2022_06_20_064517_countries_unknown_country_fix',1);
INSERT INTO `migrations` VALUES (226,'2022_06_21_045035_customer_quotes_contracts_count',1);
INSERT INTO `migrations` VALUES (227,'2022_06_21_091733_recalculate',1);
INSERT INTO `migrations` VALUES (228,'2022_06_22_060153_locales_rename',1);
INSERT INTO `migrations` VALUES (229,'2022_06_28_154643_audit_remove_empty_records',1);
INSERT INTO `migrations` VALUES (230,'2022_07_01_071953_documents_oem_said_length',1);
INSERT INTO `migrations` VALUES (231,'2022_07_06_085000_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (232,'2022_07_21_052237_sync_permissions',1);
INSERT INTO `migrations` VALUES (233,'2022_08_05_072503_fields_create',1);
INSERT INTO `migrations` VALUES (234,'2022_08_10_060408_document_field_entries_add_document_id',1);
INSERT INTO `migrations` VALUES (235,'2022_08_15_050355_organizations_keycloak_scope_add_prefix',1);
INSERT INTO `migrations` VALUES (236,'2022_08_15_071927_organizations_add_keycloak_name',1);
INSERT INTO `migrations` VALUES (237,'2022_08_31_130104_fields_drop',1);
INSERT INTO `migrations` VALUES (238,'2022_09_01_072731_document_entries_drop_discount_net_price',1);
INSERT INTO `migrations` VALUES (239,'2022_09_02_052553_document_entries_more_fields',1);
INSERT INTO `migrations` VALUES (240,'2022_09_02_120232_product_lines_and_groups',1);
INSERT INTO `migrations` VALUES (241,'2022_09_05_072355_psps_create',1);
INSERT INTO `migrations` VALUES (242,'2022_09_19_093956_data_loader_rename_settings',1);
INSERT INTO `migrations` VALUES (243,'2022_10_03_103231_users_previous_sign_in',1);
INSERT INTO `migrations` VALUES (244,'2022_10_10_063752_documents_prices_origins',1);
INSERT INTO `migrations` VALUES (245,'2022_10_11_141909_documents_recalculate',1);
INSERT INTO `migrations` VALUES (246,'2022_10_14_051736_documents_sar_amp',1);
INSERT INTO `migrations` VALUES (247,'2022_10_14_110919_assets_contracts_active_quantity',1);
INSERT INTO `migrations` VALUES (248,'2022_10_17_123340_asset_warranties_refactor',1);
INSERT INTO `migrations` VALUES (249,'2022_10_18_071521_assets_last_warranty',1);
INSERT INTO `migrations` VALUES (250,'2022_10_20_083213_assets_recalculate',1);
INSERT INTO `migrations` VALUES (251,'2022_10_24_071805_notes_quote_and_change_requests',1);
INSERT INTO `migrations` VALUES (252,'2022_10_24_083822_notes_note_nullable',1);
INSERT INTO `migrations` VALUES (253,'2022_10_25_053607_assets_eosl',1);
INSERT INTO `migrations` VALUES (254,'2022_10_26_090433_document_entries_uid_removed_at',1);
INSERT INTO `migrations` VALUES (255,'2022_10_28_040953_assets_contacts_active_quantity_rename',1);
INSERT INTO `migrations` VALUES (256,'2022_11_23_061845_users_freshchat_id',1);
INSERT INTO `migrations` VALUES (257,'2022_12_08_100652_search_rebuild_indexes',1);
INSERT INTO `migrations` VALUES (258,'2022_12_20_051643_synced_at_nullable',1);
INSERT INTO `migrations` VALUES (259,'2022_12_21_122727_asset_warranties_key',1);
INSERT INTO `migrations` VALUES (260,'2022_12_22_104056_document_entries_rename_uid_to_key',1);
INSERT INTO `migrations` VALUES (261,'2022_12_26_105427_laravel_jobs_tables_update',1);
INSERT INTO `migrations` VALUES (262,'2023_01_09_073522_dataloader_hash',1);
INSERT INTO `migrations` VALUES (263,'2023_01_17_093031_analyze_assets_drop',1);
