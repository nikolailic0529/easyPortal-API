<?php declare(strict_types = 1);

namespace App\Utils;

use function array_slice;
use function end;
use function explode;
use function implode;

class ModelProperty {
    protected string  $name;
    protected ?string $relation;
    /**
     * @var array<string>|null
     */
    protected ?array $path;

    public function __construct(string $property) {
        $parts          = explode('.', $property);
        $this->name     = (string) end($parts);
        $this->path     = array_slice($parts, 0, -1) ?: null;
        $this->relation = implode('.', (array) $this->path) ?: null;
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

    public function getRelation(): ?string {
        return $this->relation;
    }

    public function isRelation(): bool {
        return $this->relation !== null;
    }
}
