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
    public const DATA_LOADER_DEFAULT = 'data-loader-default';

    /**
     * Default queue for Data Loader Update jobs.
     */
    public const KEYCLOAK_DEFAULT = 'keycloak-default';
}
