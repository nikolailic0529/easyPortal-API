<?php declare(strict_types = 1);

/**
 * This file contains a list of application settings except for passwords and
 * other sensitive data (lease use .env for them).
 *
 * Settings priorities:
 * - .env
 * - this file
 * - other configuration files
 */

// @phpcs:disable PSR1.Files.SideEffects

use App\Jobs\Queues;

/**
 * This file should be loaded only once.
 * =============================================================================
 */

if (defined('CONSTANTS')) {
    return;
}

define('CONSTANTS', true);

// <editor-fold desc="Data Loader">
// =============================================================================
// Enabled?
define('DATA_LOADER_ENABLED', true);

// Chunk size (default: 100)
define('DATA_LOADER_CHUNK', 100);

// <editor-fold desc="ResellersImporterCronJob">
// -----------------------------------------------------------------------------
// Enabled?
define('DATA_LOADER_RESELLERS_IMPORTER_ENABLED', DATA_LOADER_ENABLED);

// Cron expression (default: daily)
define('DATA_LOADER_RESELLERS_IMPORTER_CRON', '0 0 * * *');

// Queue name
define('DATA_LOADER_RESELLERS_IMPORTER_QUEUE', Queues::DATA_LOADER_DEFAULT);
// </editor-fold>

define('DATA_LOADER_RESELLER_UPDATE_QUEUE', Queues::DATA_LOADER_RESELLER);
// </editor-fold>
