<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Properties\Property;
use App\Utils\Eloquent\ModelProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LogicException;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function is_array;
use function json_encode;
use function sha1;
use function sprintf;
use function str_starts_with;

class Configuration {
    protected const METADATA   = 'metadata';
    protected const PROPERTIES = 'properties';

    /**
     * @var array<string,\App\Services\Search\Properties\Property|array<mixed>>
     */
    protected array $properties;

    /**
     * @param \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $model
     * @param array<string,\App\Services\Search\Properties\Property|array<mixed>>          $metadata
     * @param array<string,\App\Services\Search\Properties\Property|array<mixed>>          $properties
     */
    public function __construct(
        protected Model $model,
        array $metadata,
        array $properties,
    ) {
        $this->properties = $this->buildProperties($metadata, $properties);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
     */
    public function getModel(): Model {
        return $this->model;
    }

    /**
     * @return array<string,\App\Services\Search\Properties\Property|array<mixed>>
     */
    public function getProperties(): array {
        return $this->properties;
    }

    /**
     * @return array<string>
     */
    public function getRelations(): array {
        return (new Collection($this->getProperties()))
            ->flatten()
            ->map(static function (Property $property): ModelProperty {
                return new ModelProperty($property->getName());
            })
            ->filter(static function (ModelProperty $property): bool {
                return $property->isRelation();
            })
            ->map(static function (ModelProperty $property): string {
                return $property->getRelationName();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string>
     */
    public function getSearchable(): array {
        // Convert into flat array: ['properties.id` => new Property(), 'properties.name` => new Property()]
        $properties = $this->getSearchableProcess($this->getProperties());
        $searchable = array_keys(array_filter($properties, static function (?Property $property): bool {
            return (bool) $property?->isSearchable();
        }));

        return $searchable;
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property|mixed> $properties
     *
     * @return array<string,\App\Services\Search\Properties\Property|null>
     */
    protected function getSearchableProcess(array $properties, string $prefix = null): array {
        $flat = [];

        foreach ($properties as $name => $property) {
            $key = $prefix ? "{$prefix}.{$name}" : $name;

            if ($property instanceof Property) {
                $flat[$key] = $property;
            } elseif (is_array($property)) {
                $flat = array_merge($flat, $this->getSearchableProcess($property, $key) ?: [$key => null]);
            } else {
                // ignore
            }
        }

        return $flat;
    }

    public function getProperty(string $name): ?Property {
        return Arr::get($this->getProperties(), $name) ?: null;
    }

    public function getIndexName(): string {
        $hash = sha1(json_encode($this->getMappings()));
        $name = "{$this->getIndexAlias()}@{$hash}";

        return $name;
    }

    public function getIndexAlias(): string {
        return $this->getModel()->scoutSearchableAs();
    }

    public function isIndex(string $index): bool {
        return str_starts_with($index, "{$this->getIndexAlias()}@");
    }

    /**
     * @return array<mixed>
     */
    public function getMappings(): array {
        return [
            'properties' => $this->getMappingsProcess($this->getProperties()),
        ];
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property|mixed> $properties
     *
     * @return array<mixed>
     */
    protected function getMappingsProcess(array $properties): array {
        $mappings = [];

        foreach ($properties as $name => $property) {
            if (is_array($property)) {
                $mappings[$name] = [
                    'properties' => $this->getMappingsProcess($property),
                ];
            } else {
                $mappings[$name] = array_filter([
                    'type'   => $property->getType(),
                    'fields' => $property->getFields(),
                ]);
            }
        }

        return $mappings;
    }

    public static function getMetadataName(string $name = ''): string {
        return static::METADATA.($name ? ".{$name}" : '');
    }

    public static function getPropertyName(string $name = ''): string {
        return static::PROPERTIES.($name ? ".{$name}" : '');
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property|array<mixed>> $metadata
     * @param array<string,\App\Services\Search\Properties\Property|array<mixed>> $properties
     *
     * @return array<string,\App\Services\Search\Properties\Property|array<mixed>>
     */
    protected function buildProperties(array $metadata, array $properties): array {
        $model      = $this->getModel();
        $properties = [
            static::METADATA   => $metadata,
            static::PROPERTIES => $properties,
        ];

        foreach ($model->getGlobalScopes() as $scope) {
            if ($scope instanceof ScopeWithMetadata) {
                foreach ($scope->getSearchMetadata($model) as $key => $metadata) {
                    // Metadata should be unique to avoid any possible side effects.
                    if (array_key_exists($key, $properties[static::METADATA])) {
                        throw new LogicException(sprintf(
                            'The `%s` trying to redefine `%s` in metadata.',
                            $scope::class,
                            $key,
                        ));
                    }

                    // Add
                    $properties[static::METADATA][$key] = $metadata;
                }
            }
        }

        return $properties;
    }
}
