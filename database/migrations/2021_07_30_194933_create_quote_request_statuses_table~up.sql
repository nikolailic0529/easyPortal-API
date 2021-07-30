SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `quote_request_statuses` (
  `id`         CHAR(36)  NOT NULL,
  `request_id` CHAR(36)  NOT NULL,
  `status_id`  CHAR(36)  NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  INDEX `fk_quote_request_statuses_quote_requests1_idx` (`request_id` ASC) VISIBLE,
  INDEX `fk_quote_request_statuses_statuses1_idx` (`status_id` ASC) VISIBLE,
  INDEX `unique__quote_request_id__status_id__deleted_not` (`request_id` ASC, `status_id` ASC, `deleted_not` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_not` ASC) VISIBLE,
  CONSTRAINT `fk_quote_request_statuses_quote_requests1`
    FOREIGN KEY (`request_id`)
    REFERENCES `quote_requests` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_statuses_statuses1`
    FOREIGN KEY (`status_id`)
    REFERENCES `statuses` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
