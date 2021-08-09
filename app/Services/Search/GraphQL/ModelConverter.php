<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use ReflectionClass;

use function array_map;
use function array_merge;
use function end;
use function explode;
use function implode;
use function is_array;
use function sha1;
use function substr;

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
        return $this->convert([$this->getModelTypeName($model)], $model::getSearchProperties());
    }

    /**
     * @param array<string>        $path
     * @param array<string, mixed> $properties
     *
     * @return array<\GraphQL\Type\Definition\InputObjectType>
     */
    protected function convert(array $path, array $properties): array {
        $types  = [];
        $fields = [];

        foreach ($properties as $property => $value) {
            if (is_array($value)) {
                $types    = array_merge($types, $this->convert([...$path, $property], $value));
                $fields[] = [
                    'name' => $property,
                    'type' => end($types),
                ];
            } else {
                $fields[] = [
                    'name' => $property,
                    'type' => Type::string(), // doesn't matter now
                ];
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
        $name  = $class->getShortName();

        if ($class->isAnonymous()) {
            [$base, $path] = explode('@', $name, 2);

            $name = $base.substr(sha1($path), 0, 7);
        } else {
            $name = Str::plural($name);
        }

        return "{$name}Search";
    }
}
