<?php declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects

/**
 * This file contains a list of application settings except passwords. You can
 * use `null` to use the default value for the appropriate setting.
 *
 * Settings priorities:
 * - .env
 * - this file
 * - other configuration files
 */

if (defined('CONSTANTS')) {
    return;
}

define('CONSTANTS', true);

/**
 * Data Loader
 *
 * @see ./data-loader.php
 */
define('DATA_LOADER_ENABLED', true);
define('DATA_LOADER_CHUNK', null);
