<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Services\Service;
use Exception;
use Throwable;

use function __;

abstract class ApplicationException extends Exception {
    use HasErrorCode;

    private ?string $level   = null;
    private ?string $channel = null;

    /**
     * @var array<string,mixed>
     */
    private array $context = [];

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

    /**
     * @param array<string, scalar|\Stringable> $replacements
     */
    protected function translate(?Exception $service, string $message, array $replacements = []): string {
        if ($service) {
            $name = Service::getServiceName($service);

            if ($name) {
                $message = "services.{$name}.{$message}";
            }
        }

        return __($message, $replacements);
    }
}
