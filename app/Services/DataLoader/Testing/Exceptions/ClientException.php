<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Exceptions;

use App\Services\DataLoader\ServiceException;
use Throwable;

use function sprintf;

class ClientException extends ServiceException {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        protected string $path,
        protected string $selector,
        protected string $query,
        protected array $variables,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('GraphQL Dump for `%s` not found (`%s`).', $this->selector, $this->path),
            $previous,
        );

        $this->setContext([
            'path'      => $this->path,
            'selector'  => $this->selector,
            'query'     => $this->query,
            'variables' => $this->variables,
        ]);
    }
}
