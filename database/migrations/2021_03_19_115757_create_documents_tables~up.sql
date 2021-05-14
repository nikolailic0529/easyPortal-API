SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `documents` (
    `id`          CHAR(36)       NOT NULL,
    `oem_id`      CHAR(36)       NOT NULL,
    `type_id`     CHAR(36)       NOT NULL,
    `customer_id` CHAR(36)       NOT NULL,
    `reseller_id` CHAR(36)       NOT NULL,
    `number`      VARCHAR(64)    NOT NULL COMMENT 'Internal Number',
    `start`       TIMESTAMP      NOT NULL,
    `end`         TIMESTAMP      NOT NULL,
    `price`       DECIMAL(12, 2) NOT NULL,
    `currency_id` CHAR(36)       NOT NULL,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP      NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_documents_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_documents_customers1_idx`(`customer_id` ASC) VISIBLE,
    INDEX `fk_documents_oems1_idx`(`oem_id` ASC) VISIBLE,
    INDEX `fk_documents_currencies1_idx`(`currency_id` ASC) VISIBLE,
    INDEX `fk_documents_resellers1_idx`(`reseller_id` ASC) VISIBLE,
    CONSTRAINT `fk_documents_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_documents_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_documents_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_documents_currencies1`
        FOREIGN KEY (`currency_id`)
            REFERENCES `currencies`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_documents_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `document_entries` (
    `id`          CHAR(36)             NOT NULL,
    `oem_id`      CHAR(36)             NOT NULL,
    `document_id` CHAR(36)             NOT NULL,
    `asset_id`    CHAR(36)             NOT NULL,
    `product_id`  CHAR(36)             NOT NULL,
    `quantity`    SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP            NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_document_entries_documents1_idx`(`document_id` ASC) VISIBLE,
    INDEX `fk_document_entries_assets1_idx`(`asset_id` ASC) VISIBLE,
    INDEX `fk_document_entries_products1_idx`(`product_id` ASC) VISIBLE,
    INDEX `fk_document_entries_oems1_idx`(`oem_id` ASC) VISIBLE,
    CONSTRAINT `fk_document_entries_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_document_entries_assets1`
        FOREIGN KEY (`asset_id`)
            REFERENCES `assets`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_document_entries_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_document_entries_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
