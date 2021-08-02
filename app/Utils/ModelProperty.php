<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    public function getValue(Model $model): mixed {
        $value = null;

        if ($this->isRelation()) {
            $value    = $model;
            $previous = [];

            foreach ($this->getPath() as $property) {
                if ($value instanceof Collection) {
                    $value = $value->pluck($property)->flatten(1)->unique();
                } elseif ($value instanceof Model) {
                    $value = $value->{$property};
                } else {
                    throw new LogicException(sprintf(
                        'Property `%s` is not a relation.',
                        implode('.', $previous),
                    ));
                }

                $previous[] = $property;
            }
        } else {
            $value = $model->{$this->getName()};
        }

        return $value;
    }
}
