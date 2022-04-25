<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use GraphQL\Server\OperationParams;
use Throwable;

use function __;

class GraphQLQueryInvalid extends ExportException {
    /**
     * @param array<mixed> $errors
     */
    public function __construct(
        protected OperationParams $params,
        protected array $errors,
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL query invalid.', $previous);

        $this->setContext([
            'operation' => $this->params->operation,
            'queryId'   => $this->params->queryId,
            'query'     => $this->params->query,
            'variables' => $this->params->variables,
            'errors'    => $this->errors,
        ]);
    }

    public function getErrorMessage(): string {
        return __('http.controllers.export.graphql_query_invalid');
    }
}
