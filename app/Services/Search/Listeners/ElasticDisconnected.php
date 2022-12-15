<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Exceptions\ErrorReport;
use App\Services\Search\Elastic\ClientBuilder;
use App\Services\Search\Exceptions\ElasticUnavailable;
use App\Utils\Providers\EventsProvider;
use Elastic\Client\ClientBuilderInterface;

/**
 * Resets cached Client instances when connection is closed/failed/etc. It is
 * required for queue to avoid failing all other queued jobs which will use
 * broken client otherwise (seems there is no other way to reconnect).
 *
 * @see ClientBuilderInterface
 */
class ElasticDisconnected implements EventsProvider {
    public function __construct(
        protected ClientBuilderInterface $builder,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            ErrorReport::class,
        ];
    }

    public function __invoke(ErrorReport $event): void {
        if ($event->getError() instanceof ElasticUnavailable && $this->builder instanceof ClientBuilder) {
            $this->builder->reset();
        }
    }
}
