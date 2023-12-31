<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Http\Controllers\Export\Events\QueryExported;
use App\Services\Audit\Enums\Action;

class ExportListener extends Listener {
    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            QueryExported::class,
        ];
    }

    public function __invoke(QueryExported $event): void {
        $this->auditor->create($this->org, Action::exported(), null, [
            'type'  => $event->getType(),
            'query' => $event->getQuery(),
        ]);
    }
}
