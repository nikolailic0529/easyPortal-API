SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `product_lines` (
    `id`          CHAR(36)     NOT NULL,
    `oem_id`      CHAR(36)     NOT NULL,
    `key`         VARCHAR(255) NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique__key__oem_id__deleted_not`(`key` ASC, `oem_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    INDEX `fk_product_lines_oems1_idx`(`oem_id` ASC) VISIBLE,
    CONSTRAINT `fk_product_lines_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `product_groups` (
    `id`          CHAR(36)     NOT NULL,
    `key`         VARCHAR(255) NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique__key__deleted_not`(`key` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE
);

ALTER TABLE `document_entries`
    ADD COLUMN `product_line_id`  CHAR(36) NULL DEFAULT NULL AFTER `product_id`,
    ADD COLUMN `product_group_id` CHAR(36) NULL DEFAULT NULL AFTER `product_line_id`,
    ADD INDEX `fk_document_entries_product_groups1_idx`(`product_group_id` ASC) VISIBLE,
    ADD INDEX `fk_document_entries_product_lines1_idx`(`product_line_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_product_groups1`
        FOREIGN KEY (`product_group_id`)
            REFERENCES `product_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_document_entries_product_lines1`
        FOREIGN KEY (`product_line_id`)
            REFERENCES `product_lines`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
