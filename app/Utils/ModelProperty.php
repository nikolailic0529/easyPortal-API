<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use LogicException;

use function array_slice;
use function end;
use function explode;
use function implode;
use function sprintf;

class ModelProperty {
    protected string  $name;
    protected ?string $relationName;

    /**
     * @var array<string>|null
     */
    protected ?array $path;

    /**
     * @var array<string>|null
     */
    protected ?array $relationPath;

    public function __construct(string $property) {
        $this->path         = explode('.', $property);
        $this->name         = (string) end($this->path);
        $this->relationPath = array_slice($this->path, 0, -1) ?: null;
        $this->relationName = implode('.', (array) $this->relationPath) ?: null;
    }

    public function isRelation(): bool {
        return $this->relationName !== null;
    }

    public function isAttribute(): bool {
        return !$this->isRelation();
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @return array<string>|null
     */
    public function getPath(): ?array {
        return $this->path;
    }

    public function getRelationName(): ?string {
        return $this->relationName;
    }

    /**
     * @return array<string>|null
     */
    public function getRelationPath(): ?array {
        return $this->relationPath;
    }

    public function getRelation(Model $model): Relation {
        $relation = $this->getRelationName();

        if (!$relation) {
            throw new PropertyIsNotRelation($model, $this->getName());
        }

        return (new ModelHelper($model))->getRelation($relation);
    }

    public function getValue(Model $model): mixed {
        $value = null;

        if ($this->isRelation()) {
            $value    = $model;
            $previous = [];

            foreach ($this->getPath() as $property) {
                if ($value === null || ($value instanceof Collection && $value->all() === [null])) {
                    $value = null;
                    break;
                } elseif ($value instanceof Collection) {
                    $value = $value->pluck($property)->flatten(1)->unique();
                } elseif ($value instanceof Model) {
                    $value = $value->getAttribute($property);
                } else {
                    throw new LogicException(sprintf(
                        'Value of `%s` is not supported.',
                        implode('.', $previous),
                    ));
                }

                $previous[] = $property;
            }
        } else {
            $value = $model->getAttribute($this->getName());
        }

        return $value;
    }
}
