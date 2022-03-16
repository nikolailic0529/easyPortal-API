<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use App\Exceptions\Handler;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use JsonSerializable;
use Throwable;

use function is_array;
use function is_callable;

class GraphQLErrorsSchema implements JsonSerializable {
    /**
     * @param array<string>|Throwable|(Closure(): array<string>|\Exception|null) $errors
     */
    public function __construct(
        protected Closure|Throwable|array $errors,
    ) {
        // empty
    }

    public function jsonSerialize(): mixed {
        return $this->getErrorsSchema();
    }

    /**
     * @return array<mixed>
     */
    protected function getErrorsSchema(): array {
        // Get errors
        $container = Container::getInstance();
        $errors    = $this->errors;

        if (is_callable($errors)) {
            $errors = $container->call($errors);
        }

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        // Generate schema
        $items   = [];
        $handler = $container->get(ExceptionHandler::class);

        foreach ($errors as $error) {
            if ($error instanceof Throwable) {
                if ($handler instanceof Handler) {
                    $error = $handler->getExceptionMessage($error);
                } else {
                    $error = $error->getMessage();
                }
            }

            $items[] = [
                'type'       => 'object',
                'required'   => [
                    'message',
                ],
                'properties' => [
                    'message' => [
                        'const' => $error,
                    ],
                ],
            ];
        }

        return [
            '$schema'              => 'http://json-schema.org/draft-07/schema#',
            'type'                 => 'object',
            'additionalProperties' => true,
            'required'             => [
                'errors',
            ],
            'properties'           => [
                'errors' => [
                    'type'            => 'array',
                    'additionalItems' => false,
                    'items'           => $items,
                ],
            ],
        ];
    }
}
