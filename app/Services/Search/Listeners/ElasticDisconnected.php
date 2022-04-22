<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Events\Subscriber;
use App\Exceptions\ErrorReport;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

/**
 * Resets the {@see \Elasticsearch\Client} singleton when connection is
 * closed/failed/etc. It is required for queue to avoid failing all other
 * queued jobs which will use broken client otherwise (seems there is no
 * other way to reconnect).
 */
class ElasticDisconnected implements Subscriber {
    public function __construct(
        protected Application $application,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(ErrorReport::class, $this::class);
    }

    public function __invoke(ErrorReport $event): void {
        if ($event->getError() instanceof NoNodesAvailableException) {
            $this->application->forgetInstance(Client::class);
        }
    }
}
