<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Models\Asset;
use App\Models\Document;
use App\Services\Recalculator\Recalculator;
use App\Utils\Providers\EventsProvider;

class DocumentDeleted implements EventsProvider {
    public function __construct(
        protected Recalculator $recalculator,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            'eloquent.deleted: '.Document::class,
        ];
    }

    public function __invoke(Document $document): void {
        // Visible Contract?
        /* @see Asset::getLastWarranty() */
        if ($document->is_hidden || !$document->is_contract) {
            return;
        }

        // Get Assets
        $model = new Asset();
        $keys  = $document->assets()
            ->toBase()
            ->select($model->getQualifiedKeyName())
            ->where('warranty_end', '=', $document->end)
            ->get()
            ->pluck($model->getKeyName())
            ->all();

        $this->recalculator->dispatch([
            'model' => $model::class,
            'keys'  => $keys,
        ]);
    }
}
