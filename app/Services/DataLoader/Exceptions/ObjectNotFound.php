<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\ServiceException;
use Throwable;

abstract class ObjectNotFound extends ServiceException {
    public function __construct(
        string $message,
        protected string $key,
        protected Type|null $object = null,
        Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getObject(): ?Type {
        return $this->object;
    }
}
