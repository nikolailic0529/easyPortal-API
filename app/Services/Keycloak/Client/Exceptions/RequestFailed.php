<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use Illuminate\Http\Client\RequestException;
use Throwable;

use function __;
use function sprintf;
use function strtoupper;

class RequestFailed extends ClientException {
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected string $url,
        protected string $method,
        protected array $data,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Request failed `%s %s`.',
            strtoupper($this->method),
            $this->url,
        ), $previous);

        $this->setContext([
            'data' => $this->data,
        ]);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.request_failed');
    }

    public function isHttpError(int $code = null): bool {
        return $this->getPrevious() instanceof RequestException
            && ($code === null || $this->getPrevious()?->getCode() === $code);
    }
}
