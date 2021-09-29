SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Create Locations
INSERT INTO `locations` (`id`,
                         `country_id`,
                         `city_id`,
                         `postcode`,
                         `state`,
                         `line_one`,
                         `line_two`,
                         `latitude`,
                         `longitude`)
SELECT UUID(),
       `country_id`,
       `city_id`,
       `postcode`,
       `state`,
       `line_one`,
       `line_two`,
       `latitude`,
       `longitude`
FROM `locations_outdated`
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

-- Customers locations
INSERT INTO `customer_locations` (`id`, `customer_id`, `location_id`)
SELECT `locations_outdated`.`id`, `locations_outdated`.`object_id`, `locations`.`id`
FROM `locations_outdated`
    LEFT JOIN `locations` ON `locations`.`hash` = `locations_outdated`.`hash`
WHERE 1=1
    AND `locations_outdated`.`object_type` = 'Customer'
    AND `locations_outdated`.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

INSERT INTO `customer_location_types` (`id`, `customer_location_id`, `type_id`)
SELECT `location_types_outdated`.`id`, `location_types_outdated`.`location_id`, `location_types_outdated`.`type_id`
FROM `location_types_outdated`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `location_types_outdated`.`location_id`
WHERE 1=1
    AND `locations_outdated`.`deleted_at` IS NULL
    AND `locations_outdated`.`object_type` = 'Customer'
    AND `locations_outdated`.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

-- Resellers locations
INSERT INTO `reseller_locations` (`id`, `reseller_id`, `location_id`)
SELECT `locations_outdated`.`id`, `locations_outdated`.`object_id`, `locations`.`id`
FROM `locations_outdated`
LEFT JOIN `locations` ON `locations`.`hash` = `locations_outdated`.`hash`
WHERE 1=1
    AND `locations_outdated`.`object_type` = 'Reseller'
    AND `locations_outdated`.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

INSERT INTO `reseller_location_types` (`id`, `reseller_location_id`, `type_id`)
SELECT `location_types_outdated`.`id`, `location_types_outdated`.`location_id`, `location_types_outdated`.`type_id`
FROM `location_types_outdated`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `location_types_outdated`.`location_id`
WHERE 1=1
    AND `locations_outdated`.`deleted_at` IS NULL
    AND `locations_outdated`.`object_type` = 'Reseller'
    AND `locations_outdated`.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

-- Assets locations
INSERT INTO `tmp_locations_map` (`old_location_id`)
SELECT `assets`.`location_id`
FROM `assets`
WHERE `assets`.`location_id` IS NOT NULL
ON DUPLICATE KEY UPDATE `old_location_id` = `assets`.`location_id`;

UPDATE `tmp_locations_map`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `tmp_locations_map`.`old_location_id`
LEFT JOIN `locations` ON `locations`.`hash` = `locations_outdated`.`hash`
SET `new_location_id` = `locations`.`id`;

UPDATE `assets`
LEFT JOIN `tmp_locations_map` ON `tmp_locations_map`.`old_location_id` = `assets`.`location_id`
SET `assets`.`location_id` = `tmp_locations_map`.`new_location_id`
WHERE `assets`.`location_id` IS NOT NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
