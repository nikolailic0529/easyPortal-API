<?php declare(strict_types = 1);

namespace App\Exceptions;

use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use Illuminate\Contracts\Config\Repository;

class GraphQLHandler {
    public function __construct(
        protected Repository $config,
        protected Helper $helper,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function __invoke(GraphQLError $error): array {
        $result = [
                'message' => $this->helper->getMessage($error->getPrevious() ?? $error),
            ] + FormattedError::createFromException($error);

        if ($this->config->get('app.debug')) {
            $result['extensions']['stack'] = $this->helper->getTrace($error);
        }

        return $result;
    }
}
