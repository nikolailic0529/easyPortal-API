<?php declare(strict_types = 1);

namespace App\Services\Queue;

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
    public const DATA_LOADER = 'data-loader';

    /**
     * Recalculate queue for Data Loader
     */
    public const DATA_LOADER_RECALCULATE = 'data-loader-recalculate';

    /**
     * Default queue for Data Loader Update jobs.
     */
    public const KEYCLOAK = 'keycloak';

    /**
     * Default queue for Search and Laravel Scout.
     */
    public const SEARCH = 'search';
}
