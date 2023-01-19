<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Document;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Eloquent\Casts\Origin;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Processor<Document, DocumentsChunkData, EloquentState<Document>>
 */
class DocumentsProcessor extends Processor {
    protected function getModel(): string {
        return Document::class;
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $items = (new Collection($items))->loadMissing(['statuses', 'entries']);
        $data  = new DocumentsChunkData($items);

        return $data;
    }

    /**
     * @param EloquentState<Document> $state
     * @param DocumentsChunkData      $data
     * @param Document                $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        $item              = $this->syncAttributes($item);
        $item->is_hidden   = Document::isHidden($item->statuses);
        $item->is_contract = Document::isContractType($item->type_id);
        $item->is_quote    = Document::isQuoteType($item->type_id);

        foreach ($item->entries as $entry) {
            $this->syncAttributes($entry->setRelation('document', $item))->save();
        }

        $item->save();
    }

    /**
     * @template T of Model
     *
     * @param T $model
     *
     * @return T
     */
    private function syncAttributes(Model $model): Model {
        $casts = $model->getCasts();

        foreach ($casts as $attr => $cast) {
            if ($cast === Origin::class) {
                $model->setAttribute($attr, $model->getAttribute($attr));
            }
        }

        return $model;
    }
}
