<?php declare(strict_types = 1);

namespace App\Jobs;

/**
 * Queues names.
 */
interface Queues {
    /**
     * Sync queue.
     */
    public const SYNC = 'sync';

    /**
     * Default queue.
     */
    public const DEFAULT = 'default';

    /**
     * Default queue for Data Loader
     */
    public const DATA_LOADER_DEFAULT = 'data-loader-default';
}
