<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function __;

class GraphQLQueryInvalid extends HttpException implements HttpExceptionInterface {
    /**
     * @param array<mixed> $errors
     */
    public function __construct(
        protected array $errors,
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL query invalid.', $previous);

        $this->setContext([
            'errors' => $this->errors,
        ]);
    }

    public function getErrorMessage(): string {
        return __('export.errors.graphql_query_invalid');
    }

    public function getStatusCode(): int {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getHeaders(): mixed {
        return [];
    }
}
