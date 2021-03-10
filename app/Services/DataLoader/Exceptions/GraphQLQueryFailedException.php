<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

class GraphQLQueryFailedException extends ClientException {
    /**
     * @var array<mixed>
     */
    protected array $errors = [];

    /**
     * @return array<mixed>
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * @param array<mixed> $errors
     */
    public function setErrors(array $errors): static {
        $this->errors = $errors;

        return $this;
    }
}
