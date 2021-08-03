SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `quote_request_assets` (
  `id`               CHAR(36) NOT NULL,
  `request_id`       CHAR(36) NOT NULL,
  `asset_id`         CHAR(36) NOT NULL,
  `service_level_id` CHAR(36) NOT NULL,
  `duration_id`      CHAR(36) NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_quote_request_assets_quote_requests1_idx` (`request_id` ASC) VISIBLE,
  INDEX `fk_quote_request_assets_assets1_idx` (`asset_id` ASC) VISIBLE,
  INDEX `fk_quote_request_assets_service_levels1_idx` (`service_level_id` ASC) VISIBLE,
  INDEX `fk_quote_request_assets_durations1_idx` (`duration_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  CONSTRAINT `fk_quote_request_assets_assets1`
    FOREIGN KEY (`asset_id`)
    REFERENCES `assets` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_assets_quote_requests1`
    FOREIGN KEY (`request_id`)
    REFERENCES `quote_requests` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_assets_service_levels1`
    FOREIGN KEY (`service_level_id`)
    REFERENCES `service_levels` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_request_assets_quote_request_durations1`
    FOREIGN KEY (`duration_id`)
    REFERENCES `quote_request_durations` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_as_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
