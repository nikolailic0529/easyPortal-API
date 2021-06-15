SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `analyze_assets` (
    `id`               CHAR(36)     NOT NULL,
    `unknown`          TINYINT(1)   NULL     DEFAULT NULL,
    `reseller_null`    TINYINT(1)   NULL     DEFAULT NULL,
    `reseller_types`   VARCHAR(128) NULL     DEFAULT NULL,
    `reseller_unknown` CHAR(36)     NULL     DEFAULT NULL,
    `customer_null`    TINYINT(1)   NULL     DEFAULT NULL,
    `customer_types`   VARCHAR(128) NULL     DEFAULT NULL,
    `customer_unknown` CHAR(36)     NULL     DEFAULT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;