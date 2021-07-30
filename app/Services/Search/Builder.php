<?php declare(strict_types = 1);

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;

class Builder extends ScoutBuilder {
    public const METADATA   = 'metadata';
    public const PROPERTIES = 'searchable';

    public function __construct(Model $model, string $query, callable $callback = null, bool $softDelete = false) {
        parent::__construct($model, "{$this->getFieldProperties()}.\\*:{$query}", $callback, $softDelete);
    }

    public function whereMetadata(string $field, mixed $value): static {
        return $this->where("{$this->getFieldMetadata()}.{$field}.keyword", $value);
    }

    protected function getFieldMetadata(): string {
        return self::METADATA;
    }

    protected function getFieldProperties(): string {
        return self::PROPERTIES;
    }
}
