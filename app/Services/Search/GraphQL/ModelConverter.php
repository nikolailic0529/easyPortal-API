<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Configuration;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Value;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
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
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     *
     * @return array<\GraphQL\Type\Definition\InputObjectType>
     */
    public function toInputObjectTypes(string $model): array {
        $type       = $this->getModelTypeName($model);
        $properties = (new $model())->getSearchConfiguration()->getProperties()[Configuration::getPropertyName()];

        return $this->convert([$type], $properties);
    }

    /**
     * @param array<string>                                           $path
     * @param array<string, \App\Services\Search\Properties\Property> $properties
     *
     * @return array<\GraphQL\Type\Definition\InputObjectType>
     */
    protected function convert(array $path, array $properties): array {
        $types  = [];
        $fields = [];

        foreach ($properties as $property => $value) {
            if ($value instanceof Relation) {
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

        $types[] = new InputObjectType([
            'name'   => $this->getTypeName($path),
            'fields' => $fields,
        ]);

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
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    protected function getModelTypeName(string $model): string {
        $class = (new ReflectionClass($model));
        $name  = Str::plural($class->getShortName());
        $name  = "{$name}Search";

        return $name;
    }
}
