<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Configuration;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Value;
use Closure;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;

use function array_map;
use function array_merge;
use function end;
use function implode;
use function is_array;

class ModelConverter {
    public function __construct() {
        // empty
    }

    /**
     * @param class-string<Model&Searchable> $model
     *
     * @return array<string, Closure(): InputObjectType>
     */
    public function toInputObjectTypes(string $model): array {
        $properties = (new $model())->getSearchConfiguration()->getProperties();
        $properties = $properties[Configuration::getPropertyName()] ?? null;
        $typeName   = $this->getModelTypeName($model);
        $types      = is_array($properties) && $properties
            ? $this->convert([$typeName], $properties)
            : [];

        return $types;
    }

    /**
     * @param array<int, string>      $path
     * @param array<string, Property> $properties
     *
     * @return array<string, Closure(): InputObjectType>
     */
    protected function convert(array $path, array $properties): array {
        $types  = [];
        $fields = [];

        foreach ($properties as $property => $value) {
            if ($value instanceof Properties || $value instanceof Relation) {
                $types    = array_merge($types, $this->convert([...$path, $property], $value->getProperties()));
                $fields[] = [
                    'name' => $property,
                    'type' => end($types),
                ];
            } elseif ($value instanceof Value) {
                $fields[] = [
                    'name' => $property,
                    'type' => Type::string(), // doesn't matter now
                ];
            } else {
                // ignore
            }
        }

        $name         = $this->getTypeName($path);
        $type         = new InputObjectType([
            'name'   => $name,
            'fields' => $fields,
        ]);
        $types[$name] = static function () use ($type): InputObjectType {
            return $type;
        };

        return $types;
    }

    /**
     * @param array<string> $path
     */
    protected function getTypeName(array $path): string {
        $path = array_map(static function (string $name): string {
            return Str::studly($name);
        }, $path);
        $path = implode('', $path);
        $name = "{$path}Sort";

        return $name;
    }

    /**
     * @param class-string<Model&Searchable> $model
     */
    protected function getModelTypeName(string $model): string {
        $class = (new ReflectionClass($model));
        $name  = Str::plural($class->getShortName());
        $name  = "{$name}Search";

        return $name;
    }
}
