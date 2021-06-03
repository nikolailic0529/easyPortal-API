SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `logs` (
    `id`          CHAR(36)                             NOT NULL,
    `category`    VARCHAR(64)                          NOT NULL,
    `action`      VARCHAR(255)                         NOT NULL,
    `status`      ENUM ('active', 'success', 'failed') NULL     DEFAULT NULL,
    `parent_id`   CHAR(36)                             NULL     DEFAULT NULL,
    `index`       SMALLINT(5) UNSIGNED                 NULL     DEFAULT NULL,
    `object_type` VARCHAR(255)                         NULL     DEFAULT NULL,
    `object_id`   CHAR(36)                             NULL     DEFAULT NULL,
    `duration`    INT(10) UNSIGNED                     NULL     DEFAULT NULL,
    `created_at`  TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at` TIMESTAMP                            NULL     DEFAULT NULL,
    `statistics`  JSON                                 NULL     DEFAULT NULL,
    `context`     JSON                                 NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_logs_logs1_idx`(`parent_id` ASC) VISIBLE,
    INDEX `idx__action__category`(`category` ASC, `action` ASC) VISIBLE,
    INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) INVISIBLE,
    CONSTRAINT `fk_logs_logs1`
        FOREIGN KEY (`parent_id`)
            REFERENCES `logs`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
