<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Value;
use App\Utils\Eloquent\ModelProperty;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function explode;
use function is_array;
use function json_encode;
use function sha1;
use function sprintf;
use function str_starts_with;

class Configuration {
    protected const METADATA   = 'metadata';
    protected const PROPERTIES = 'properties';

    /**
     * @var array<string,array<string, \App\Services\Search\Properties\Property>>
     */
    protected array $properties;

    /**
     * @param \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $model
     * @param array<string,\App\Services\Search\Properties\Property>                       $metadata
     * @param array<string,\App\Services\Search\Properties\Property>                       $properties
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
     * @return array<string,\App\Services\Search\Properties\Property>
     */
    public function getProperties(): array {
        return $this->properties;
    }

    /**
     * @return array<string>
     */
    public function getRelations(): array {
        $properties = $this->getFlatProperties(
            static function (string $key, ?Property $property): ?string {
                return $property?->getName();
            },
        );
        $properties = (new Collection($properties))
            ->keys()
            ->map(static function (string $property): ModelProperty {
                return new ModelProperty($property);
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

        return $properties;
    }

    /**
     * @return array<string>
     */
    public function getSearchable(): array {
        $properties = $this->getFlatProperties(
            static function (string $key): string {
                return $key;
            },
            static function (Value $property): bool {
                return $property->isSearchable();
            },
        );
        $properties = array_keys($properties);

        return $properties;
    }

    /**
     * @param \Closure(string,?\App\Services\Search\Properties\Property): ?string $keyer
     * @param \Closure(\App\Services\Search\Properties\Value): bool|null          $filter
     *
     * @return array<string,\App\Services\Search\Properties\Value>
     */
    private function getFlatProperties(Closure $keyer, Closure $filter = null): array {
        $flat = [];

        foreach ($this->getProperties() as $key => $properties) {
            $key  = $keyer($key, null);
            $flat = array_merge($flat, $this->getFlatPropertiesProcess($properties, $keyer, $filter, $key));
        }

        return $flat;
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property> $properties
     * @param \Closure(string,\App\Services\Search\Properties\Property): string $keyer
     * @param \Closure(\App\Services\Search\Properties\Value): bool|null        $filter
     *
     * @return array<string,\App\Services\Search\Properties\Value>
     */
    private function getFlatPropertiesProcess(
        array $properties,
        Closure $keyer,
        Closure $filter = null,
        string $prefix = null,
    ): array {
        $flat = [];

        foreach ($properties as $name => $property) {
            $key = $keyer($name, $property);
            $key = $prefix ? "{$prefix}.{$key}" : $key;

            if ($property instanceof Value) {
                if ($filter === null || $filter($property)) {
                    $flat[$key] = $property;
                } else {
                    // ignore
                }
            } elseif ($property instanceof Relation) {
                $processed = $this->getFlatPropertiesProcess($property->getProperties(), $keyer, $filter, $key);

                if ($processed) {
                    $flat = array_merge($flat, $processed);
                }
            } else {
                // ignore
            }
        }

        return $flat;
    }

    public function getProperty(string $name): ?Property {
        $path       = explode('.', $name);
        $property   = null;
        $properties = $this->getProperties();

        foreach ($path as $segment) {
            if (isset($properties[$segment])) {
                if ($properties[$segment] instanceof Relation) {
                    $properties = $properties[$segment]->getProperties();
                } elseif ($properties[$segment] instanceof Property) {
                    $property   = $properties[$segment];
                    $properties = [];
                } elseif (is_array($properties[$segment])) {
                    $properties = $properties[$segment];
                } else {
                    $properties = [];
                }
            } else {
                $property = null;
                break;
            }
        }

        return $property;
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
        $mappings = [];

        foreach ($this->getProperties() as $key => $properties) {
            $mappings[$key]['properties'] = $this->getMappingsProcess($properties);
        }

        return [
            'properties' => $mappings,
        ];
    }

    /**
     * @param array<string,\App\Services\Search\Properties\Property> $properties
     *
     * @return array<mixed>
     */
    private function getMappingsProcess(array $properties): array {
        $mappings = [];

        foreach ($properties as $name => $property) {
            if ($property instanceof Relation) {
                $mappings[$name] = [
                    'properties' => $this->getMappingsProcess($property->getProperties()),
                ];
            } elseif ($property instanceof Value) {
                $mappings[$name] = array_filter([
                    'type'   => $property->getType(),
                    'fields' => $property->getFields(),
                ]);
            } else {
                // ignore
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
     * @param array<string,\App\Services\Search\Properties\Property> $metadata
     * @param array<string,\App\Services\Search\Properties\Property> $properties
     *
     * @return array<string,array<string, \App\Services\Search\Properties\Property>>
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
