<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Properties\Value;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Eloquent\ModelProperty;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function config;
use function explode;
use function is_array;
use function json_encode;
use function reset;
use function sha1;
use function sprintf;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

class Configuration {
    protected const ID         = 'id';
    protected const METADATA   = 'metadata';
    protected const PROPERTIES = 'properties';

    /**
     * @var array<string,Value|array<string, Property>>
     */
    protected array $properties;

    /**
     * @param Model&Searchable       $model
     * @param array<string,Property> $metadata
     * @param array<string,Property> $properties
     */
    public function __construct(
        protected Model $model,
        array $metadata,
        array $properties,
    ) {
        $this->properties = $this->buildProperties($metadata, $properties);
    }

    /**
     * @return Model&Searchable
     */
    public function getModel(): Model {
        return $this->model;
    }

    /**
     * @return array<string,Value|array<string, Property>>
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
                return $property !== null && !($property instanceof Properties)
                    ? $property->getName()
                    : null;
            },
        );
        $properties = (new Collection($properties))
            ->keys()
            ->map(static function (string $property): ModelProperty {
                return new ModelProperty($property);
            })
            ->map(static function (ModelProperty $property): ?string {
                return $property->getRelationName();
            })
            ->filter()
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
     * @param Closure(string,?Property): ?string $keyer
     * @param Closure(Value): bool|null          $filter
     *
     * @return array<string,Value>
     */
    private function getFlatProperties(Closure $keyer, Closure $filter = null): array {
        $flat = [];

        foreach ($this->getProperties() as $key => $properties) {
            $key = $keyer($key, null);

            if (is_array($properties)) {
                $flat = array_merge($flat, $this->getFlatPropertiesProcess($properties, $keyer, $filter, $key));
            } else {
                $processed = $this->getFlatPropertiesProcess([$key => $properties], $keyer, $filter, $key);

                if ($processed) {
                    $flat[$key] = reset($processed);
                }
            }
        }

        return $flat;
    }

    /**
     * @param array<string,Property>             $properties
     * @param Closure(string,?Property): ?string $keyer
     * @param Closure(Value): bool|null          $filter
     *
     * @return array<string,Value>
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
            $key = $key !== null && $prefix ? "{$prefix}.{$key}" : $key;

            if ($property instanceof Properties || $property instanceof Relation) {
                $processed = $this->getFlatPropertiesProcess(
                    $property->getProperties(),
                    $keyer,
                    $filter,
                    $key ?? $prefix,
                );

                if ($processed) {
                    $flat = array_merge($flat, $processed);
                }
            } elseif ($property instanceof Value) {
                if ($key !== null && ($filter === null || $filter($property))) {
                    $flat[$key] = $property;
                } else {
                    // ignore
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
                if ($properties[$segment] instanceof Properties || $properties[$segment] instanceof Relation) {
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
        $hash = sha1(json_encode($this->getMappings(), JSON_THROW_ON_ERROR));
        $name = "{$this->getIndexAlias()}@{$hash}";

        return $name;
    }

    public function getIndexAlias(): string {
        return $this->getModel()->getSearchableAsDefault();
    }

    public function isIndex(string $index): bool {
        return str_starts_with($index, "{$this->getIndexAlias()}@");
    }

    /**
     * @return array<mixed>
     */
    public function getMappings(): array {
        // Properties
        $mappings = [];

        foreach ($this->getProperties() as $key => $properties) {
            if (is_array($properties)) {
                $mappings[$key] = [
                    'properties' => $this->getMappingsProcess($properties),
                ];
            } else {
                $processed = $this->getMappingsProcess([$key => $properties]);

                if ($processed) {
                    $mappings[$key] = reset($processed);
                }
            }
        }

        // Soft Deleted?
        $isSoftDeletableModel   = (new ModelHelper($this->getModel()))->isSoftDeletable();
        $isSoftDeletableIndexed = (bool) config('scout.soft_delete', false);

        if ($isSoftDeletableModel && $isSoftDeletableIndexed) {
            $mappings['__soft_deleted'] = [
                'type' => 'byte',
            ];
        }

        // Return
        return [
            'dynamic'    => 'strict',
            'properties' => $mappings,
        ];
    }

    /**
     * @param array<string,Property> $properties
     *
     * @return array<mixed>
     */
    private function getMappingsProcess(array $properties): array {
        $mappings = [];

        foreach ($properties as $name => $property) {
            if ($property instanceof Properties || $property instanceof Relation) {
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

    public static function getId(): string {
        return self::ID;
    }

    public static function getMetadataName(string $name = ''): string {
        return self::METADATA.($name ? ".{$name}" : '');
    }

    public static function getPropertyName(string $name = ''): string {
        return self::PROPERTIES.($name ? ".{$name}" : '');
    }

    /**
     * @param array<string,Property> $metadata
     * @param array<string,Property> $properties
     *
     * @return array<string,Value|array<string, Property>>
     */
    protected function buildProperties(array $metadata, array $properties): array {
        $model      = $this->getModel();
        $properties = [
            self::ID         => new Uuid($this->getModel()->getKeyName(), false),
            self::METADATA   => $metadata,
            self::PROPERTIES => $properties,
        ];

        foreach ($model->getGlobalScopes() as $scope) {
            if ($scope instanceof ScopeWithMetadata) {
                foreach ($scope->getSearchMetadata($model) as $key => $data) {
                    // Metadata should be unique to avoid any possible side effects.
                    if (array_key_exists($key, $properties[self::METADATA])) {
                        throw new LogicException(sprintf(
                            'The `%s` trying to redefine `%s` in metadata.',
                            $scope::class,
                            $key,
                        ));
                    }

                    // Add
                    $properties[self::METADATA][$key] = $data;
                }
            }
        }

        return $properties;
    }
}
