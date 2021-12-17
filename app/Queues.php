<?php declare(strict_types = 1);

namespace App;

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

    /**
     * Default queue for Notificator.
     */
    public const NOTIFICATOR = 'notificator';
}