<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Contracts\LoggerObject;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class EloquentObject implements LoggerObject {
    public function __construct(
        protected Model $model,
    ) {
        // empty
    }

    public function getId(): ?string {
        return $this->model->getKey();
    }

    public function getType(): string {
        return $this->model->getMorphClass();
    }

    public function getModel(): Model {
        return $this->model;
    }

    public function isSoftDeletable(): bool {
        return (new ModelHelper($this->getModel()))->isSoftDeletable();
    }

    /**
     * Returns changed properties and their original values, but please note
     * that it should be used only in model events handler (`created`,
     * `updated`, etc) or you will get unexpected results.
     *
     * @return array<string,array{value:mixed,previous:mixed}>
     */
    public function getChanges(): array {
        $model   = $this->getModel();
        $changes = [];

        foreach ($model->getDirty() as $field => $value) {
            $changes[$field] = [
                'value'    => $value,
                'previous' => $model->getRawOriginal($field),
            ];
        }

        return $changes;
    }

    /**
     * @return array<string,array{value:mixed,previous:mixed}>
     */
    public function getProperties(): array {
        $model      = $this->getModel();
        $properties = [];

        foreach ($model->getAttributes() as $field => $value) {
            $properties[$field] = [
                'value'    => $value,
                'previous' => null,
            ];
        }

        return $properties;
    }
}
