<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Events\Subscriber;
use App\Models\Asset;
use App\Models\Document;
use App\Services\Recalculator\Recalculator;
use Illuminate\Contracts\Events\Dispatcher;

class DocumentDeleted implements Subscriber {
    public function __construct(
        protected Recalculator $recalculator,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            'eloquent.deleted: '.Document::class,
            $this::class,
        );
    }

    public function __invoke(Document $document): void {
        // Visible Contract?
        /* @see Asset::getLastWarranty() */
        if (!$document->is_visible || !$document->is_contract) {
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
