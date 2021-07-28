<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use function array_slice;
use function end;
use function explode;
use function implode;

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

    public function getValue(Model $model): mixed {
        $value = null;

        if ($this->isRelation()) {
            $models = new Collection([$model]);

            foreach ($this->getPath() as $property) {
                $models = $models->pluck($property)->flatten(1)->unique();
            }

            $value = $models;
        } else {
            $value = $model->{$this->getName()};
        }

        return $value;
    }
}
