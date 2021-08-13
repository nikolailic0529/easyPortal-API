<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Properties\Property;
use App\Utils\ModelProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LogicException;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_walk_recursive;
use function count;
use function explode;
use function implode;
use function is_array;
use function json_encode;
use function sha1;
use function sort;
use function sprintf;
use function str_ends_with;
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
        return $this->getSearchableProcess($this->getProperties()) ?: [''];
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property|mixed> $properties
     *
     * @return array<string>
     */
    protected function getSearchableProcess(array $properties, string $prefix = null): array {
        // Process
        $searchable = [];

        foreach ($properties as $name => $property) {
            if ($property instanceof Property) {
                if ($property->isSearchable()) {
                    $searchable[] = $name;
                }
            } elseif (is_array($property)) {
                $searchable = array_merge($searchable, $this->getSearchableProcess($property, $name));
            } else {
                // ignore
            }
        }

        // All properties searchable
        $keys  = array_keys($properties);
        $names = (new Collection($searchable))
            ->map(static function (string $name): string {
                return str_ends_with($name, '.*')
                    ? explode('.', $name, 2)[0]
                    : $name;
            })
            ->all();

        sort($keys);
        sort($names);

        if ($keys === $names) {
            $searchable = count($keys) > 0 ? ['*'] : [];
        }

        // Add prefix
        if ($prefix) {
            $searchable = array_map(static function (string $name) use ($prefix): string {
                return "{$prefix}.{$name}";
            }, $searchable);
        }

        // Return
        return $searchable;
    }

    public function getProperty(string $name): ?Property {
        return Arr::get($this->getProperties(), $name) ?: null;
    }

    public function getIndexName(): string {
        $properties = $this->getProperties();

        array_walk_recursive($properties, static function (mixed &$value): void {
            $value = $value instanceof Property
                ? implode('@', [$value::class, $value->getName()])
                : (string) $value;
        });

        $hash = sha1(json_encode($properties));
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
