SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `logs` (
    `id`                   CHAR(36)                             NOT NULL,
    `type`                 VARCHAR(64)                          NOT NULL,
    `action`               VARCHAR(255)                         NOT NULL,
    `status`               ENUM ('active', 'success', 'failed') NOT NULL,
    `guard`                VARCHAR(45)                          NULL     DEFAULT NULL,
    `auth_id`              CHAR(36)                             NULL     DEFAULT NULL,
    `parent_id`            CHAR(36)                             NULL     DEFAULT NULL,
    `index`                SMALLINT(5) UNSIGNED                 NULL     DEFAULT NULL,
    `duration`             INT(11)                              NULL     DEFAULT NULL,
    `entries_count`        MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_emergency`    MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_alert`        MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_critical`     MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_error`        MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_warning`      MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_notice`       MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_info`         MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `entries_debug`        MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `models_created`       MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `models_updated`       MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `models_restored`      MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `models_deleted`       MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `models_force_deleted` MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `jobs_dispatched`      MEDIUMINT(8) UNSIGNED                NOT NULL DEFAULT 0,
    `created_at`           TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at`          TIMESTAMP                            NULL     DEFAULT NULL,
    `context`              JSON                                 NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_logs_logs1_idx`(`parent_id` ASC) VISIBLE,
    INDEX `idx__type__action`(`type` ASC, `action` ASC) VISIBLE,
    CONSTRAINT `fk_logs_logs1`
        FOREIGN KEY (`parent_id`)
            REFERENCES `logs`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `log_entries` (
    `id`          CHAR(36)                                                                               NOT NULL,
    `log_id`      CHAR(36)                                                                               NOT NULL,
    `index`       MEDIUMINT(8) UNSIGNED                                                                  NOT NULL,
    `level`       ENUM ('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug') NOT NULL DEFAULT 'info',
    `event`       VARCHAR(255)                                                                           NOT NULL,
    `object_type` VARCHAR(255)                                                                           NULL     DEFAULT NULL,
    `object_id`   CHAR(36)                                                                               NULL     DEFAULT NULL,
    `created_at`  TIMESTAMP                                                                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP                                                                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `context`     JSON                                                                                   NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_log_entries_logs1_idx`(`log_id` ASC) VISIBLE,
    INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) VISIBLE,
    CONSTRAINT `fk_log_entries_logs1`
        FOREIGN KEY (`log_id`)
            REFERENCES `logs`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
