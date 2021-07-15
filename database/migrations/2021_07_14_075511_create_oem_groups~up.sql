SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    ADD COLUMN `oem_said`     VARCHAR(128) NULL DEFAULT NULL AFTER `oem_id`,
    ADD COLUMN `oem_group_id` CHAR(36)     NULL DEFAULT NULL AFTER `oem_said`,
    ADD INDEX `fk_documents_oem_groups1_idx`(`oem_group_id` ASC) VISIBLE;

CREATE TABLE IF NOT EXISTS `oem_groups` (
    `id`          CHAR(36)     NOT NULL,
    `oem_id`      CHAR(36)     NOT NULL,
    `key`         VARCHAR(64)  NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    INDEX `fk_oem_groups_oems1_idx`(`oem_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique__key__name__oem_id__deleted_not`(`key` ASC, `name` ASC, `oem_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_oem_groups_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

ALTER TABLE `documents`
    ADD CONSTRAINT `fk_documents_oem_groups1`
        FOREIGN KEY (`oem_group_id`)
            REFERENCES `oem_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
