<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Http\Controllers\Export\Events\QueryExported;
use App\Services\Audit\Enums\Action;
use Illuminate\Contracts\Events\Dispatcher;

class ExportListener extends Listener {
    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(QueryExported::class, $this::class);
    }

    public function __invoke(QueryExported $event): void {
        $this->auditor->create($this->org, Action::exported(), null, [
            'type'  => $event->getType(),
            'query' => $event->getQuery(),
        ]);
    }
}
