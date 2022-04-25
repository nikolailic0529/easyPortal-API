<?php declare(strict_types = 1);

namespace App\Rules\GraphQL;

use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;

use function __;
use function array_filter;
use function assert;
use function count;
use function is_array;
use function is_string;
use function iterator_to_array;
use function reset;

class Query implements Rule, DataAwareRule {
    /**
     * @var array<mixed>
     */
    protected array $data = [];

    public function __construct(
        protected Helper $helper,
        protected GraphQL $graphQL,
        protected SchemaBuilder $schemaBuilder,
        protected ProvidesValidationRules $rulesProvider,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // Prepare
        $operation = $this->getOperation($value);
        $node      = $this->getDocumentNode($operation);

        if (!$node) {
            return false;
        }

        // Query?
        $count   = count($node->definitions);
        $queries = array_filter(
            iterator_to_array($node->definitions),
            static function (mixed $definition): bool {
                return $definition instanceof OperationDefinitionNode
                    && $definition->operation === 'query';
            },
        );

        if ($count > 1 || count($queries) !== 1) {
            return false;
        }

        // Validate
        $rules  = $this->getValidationRules();
        $schema = $this->schemaBuilder->schema();
        $errors = DocumentValidator::validate($schema, $node, $rules);
        $valid  = count($errors) === 0;

        // Return
        return $valid;
    }

    public function message(): string {
        return __('validation.graphql_query');
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(mixed $data): static {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array<mixed>|array{query: string, variables: array<mixed>|null, operationName: string|null} $parameters
     */
    protected function createOperation(array $parameters): OperationParams {
        $parameters = array_filter($parameters);
        $operation  = $this->helper->parseRequestParams('GET', [], Arr::only($parameters, [
            'query',
            'variables',
            'operationName',
        ]));

        if (is_array($operation)) {
            $operation = reset($operation);
        }

        assert($operation instanceof OperationParams);

        return $operation;
    }

    protected function getOperation(mixed $value): ?OperationParams {
        $operation = null;

        try {
            if (is_array($value)) {
                $operation = $this->createOperation($value);
            } elseif (is_string($value)) {
                $operation = $this->createOperation([
                    'query'         => $value,
                    'variables'     => $this->data['variables'] ?? null,
                    'operationName' => $this->data['operationName'] ?? null,
                ]);
            } else {
                // empty
            }

            if ($operation) {
                $errors = $this->helper->validateOperationParams($operation);

                if ($errors) {
                    $operation = null;
                }
            }
        } catch (Exception) {
            $operation = null;
        }

        return $operation;
    }

    protected function getDocumentNode(?OperationParams $operation): ?DocumentNode {
        $node = null;

        try {
            if ($operation) {
                $node = $this->graphQL->parse($operation->query);
            }
        } catch (Exception) {
            $node = null;
        }

        return $node;
    }

    /**
     * @return array<ValidationRule>
     */
    protected function getValidationRules(): array {
        return (array) $this->rulesProvider->validationRules();
    }
}
