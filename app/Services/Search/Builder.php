<?php declare(strict_types = 1);

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;

class Builder extends ScoutBuilder {
    public const METADATA   = 'metadata';
    public const PROPERTIES = 'searchable';

    /**
     * The "where not" constraints added to the query.
     *
     * @var array<string,mixed>
     */
    public array $whereNots = [];

    /**
     * The "where not in" constraints added to the query.
     *
     * @var array<string,array<mixed>>
     */
    public array $whereNotIns = [];

    public function __construct(Model $model, string $query, callable $callback = null, bool $softDelete = false) {
        parent::__construct($model, "{$this->getFieldProperties()}.\\*:{$query}", $callback, $softDelete);
    }

    public function whereNot(string $field, mixed $value): static {
        $this->whereNots[$field] = $value;

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    public function whereNotIn(string $field, array $values): static {
        $this->whereNotIns[$field] = $values;

        return $this;
    }

    public function whereMetadata(string $field, mixed $value): static {
        return $this->where("{$this->getFieldMetadata()}.{$field}.keyword", $value);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereMetadataIn(string $field, array $values): static {
        return $this->whereIn("{$this->getFieldMetadata()}.{$field}.keyword", $values);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereMetadataNotIn(string $field, array $values): static {
        return $this->whereNotIn("{$this->getFieldMetadata()}.{$field}.keyword", $values);
    }

    protected function getFieldMetadata(): string {
        return self::METADATA;
    }

    protected function getFieldProperties(): string {
        return self::PROPERTIES;
    }
}
