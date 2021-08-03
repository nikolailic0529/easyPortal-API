<?php declare(strict_types = 1);

namespace App\Services\Queue;

/**
 * Queues names.
 */
interface Queues {
    /**
     * Default queue.
     */
    public const DEFAULT = 'default';

    /**
     * Default queue for Data Loader
     */
    public const DATA_LOADER = 'data-loader';

    /**
     * Default queue for Data Loader Update jobs.
     */
    public const KEYCLOAK = 'keycloak';

    /**
     * Default queue for Laravel Scout.
     */
    public const SCOUT = 'scout';
}
