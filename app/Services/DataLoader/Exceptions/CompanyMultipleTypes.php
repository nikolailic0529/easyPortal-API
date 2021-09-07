<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\ServiceException;
use Throwable;

use function implode;
use function sprintf;

class CompanyMultipleTypes extends ServiceException {
    /**
     * @param array<string> $types
     */
    public function __construct(
        protected string $key,
        protected array $types,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Company `%s` has multiple types: `%s`.',
            $key,
            implode('`, `', $types),
        ), $previous);
    }

    public function getKey(): string {
        return $this->key;
    }

    /**
     * @return array<string>
     */
    public function getTypes(): array {
        return $this->types;
    }
}
