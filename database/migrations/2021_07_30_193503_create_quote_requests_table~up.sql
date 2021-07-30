SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `quote_requests` (
  `id`              CHAR(36) NOT NULL,
  `oem_id`          CHAR(36) NOT NULL,
  `organization_id` CHAR(36) NOT NULL,
  `user_id`         CHAR(36) NOT NULL,
  `customer_id`     CHAR(36) NOT NULL,
  `contact_id`      CHAR(36) NOT NULL,
  `type_id`         CHAR(36) NOT NULL,
  `message`         TEXT NULL DEFAULT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_quote_requests_oems1_idx` (`oem_id` ASC) VISIBLE,
  INDEX `fk_quote_requests_organizations1_idx` (`organization_id` ASC) VISIBLE,
  INDEX `fk_quote_requests_users1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_quote_requests_customers1_idx` (`customer_id` ASC) VISIBLE,
  INDEX `fk_quote_requests_contacts1_idx` (`contact_id` ASC) VISIBLE,
  INDEX `fk_quote_requests_types1_idx` (`type_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  CONSTRAINT `fk_quote_requests_oems1`
    FOREIGN KEY (`oem_id`)
    REFERENCES `oems` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_organizations1`
    FOREIGN KEY (`organization_id`)
    REFERENCES `organizations` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_customers1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_contacts1`
    FOREIGN KEY (`contact_id`)
    REFERENCES `contacts` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_quote_requests_types1`
    FOREIGN KEY (`type_id`)
    REFERENCES `types` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
