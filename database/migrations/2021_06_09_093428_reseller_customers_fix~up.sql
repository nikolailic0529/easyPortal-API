INSERT IGNORE INTO `reseller_customers` (`id`, `reseller_id`, `customer_id`)
SELECT UUID(), `assets`.`reseller_id`, `assets`.`customer_id`
FROM `assets`
    LEFT JOIN `reseller_customers` ON `reseller_customers`.`customer_id` = `assets`.`customer_id`
WHERE 1=1
    AND `assets`.`reseller_id` IS NOT NULL
    AND `assets`.`customer_id` IS NOT NULL
    AND `reseller_customers`.`id` IS NULL;

INSERT IGNORE INTO `reseller_customers` (`id`, `reseller_id`, `customer_id`)
SELECT UUID(), `documents`.`reseller_id`, `documents`.`customer_id`
FROM `documents`
    LEFT JOIN `reseller_customers` ON `reseller_customers`.`customer_id` = `documents`.`customer_id`
WHERE 1=1
    AND `documents`.`reseller_id` IS NOT NULL
    AND `documents`.`customer_id` IS NOT NULL
    AND `reseller_customers`.`id` IS NULL;
