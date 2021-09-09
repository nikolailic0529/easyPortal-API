<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Throwable;

abstract class ApplicationException extends Exception {
    use HasErrorCode;

    protected ?string $level   = null;
    protected ?string $channel = null;

    /**
     * @var array<string,mixed>
     */
    protected array $context = [];

    protected function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }

    public function getLevel(): ?string {
        return $this->level;
    }

    public function setLevel(?string $level): static {
        $this->level = $level;

        return $this;
    }

    public function getChannel(): ?string {
        return $this->channel;
    }

    public function setChannel(?string $channel): static {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * @param array<string,mixed> $context
     */
    protected function setContext(array $context): static {
        $this->context = $context;

        return $this;
    }
}
