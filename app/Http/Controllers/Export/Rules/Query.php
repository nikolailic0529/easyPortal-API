<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use Closure;
use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Executor\Values;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;

use function array_filter;
use function assert;
use function count;
use function is_array;
use function is_string;
use function iterator_to_array;
use function reset;

class Query implements InvokableRule, DataAwareRule {
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
     * @inheritDoc
     */
    public function __invoke($attribute, $value, $fail): void {
        // Prepare
        try {
            $operation    = $this->getOperation($value);
            $documentNode = $this->getDocumentNode($operation);
        } catch (Exception $exception) {
            $this->fail($fail, [$exception]);

            return;
        }

        // Query?
        $count         = count($documentNode->definitions);
        $queries       = array_filter(
            iterator_to_array($documentNode->definitions),
            static function (mixed $definition): bool {
                return $definition instanceof OperationDefinitionNode
                    && $definition->operation === 'query';
            },
        );
        $operationNode = reset($queries);

        if ($count > 1 || count($queries) !== 1 || !($operationNode instanceof OperationDefinitionNode)) {
            $this->fail($fail);

            return;
        }

        // Validate query
        $rules  = $this->getValidationRules();
        $schema = $this->schemaBuilder->schema();
        $errors = DocumentValidator::validate($schema, $documentNode, $rules);

        if ($errors) {
            $this->fail($fail, $errors);

            return;
        }

        // Validate variables
        $values = Values::getVariableValues(
            $schema,
            $operationNode->variableDefinitions ?? [],
            $operation->variables ?? [],
        );
        $errors = reset($values);

        if ($errors) {
            $this->fail($fail, $errors);
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function setData(mixed $data): static {
        $this->data = $data;

        return $this;
    }

    /**
     * @param Closure(string): PotentiallyTranslatedString $fail
     * @param array<mixed>                                 $errors
     */
    protected function fail(Closure $fail, array $errors = []): void {
        $error = reset($errors);

        if ($error instanceof Exception && $error instanceof ClientAware && $error->isClientSafe()) {
            $fail($error->getMessage());
        } else {
            $fail('validation.graphql_query')->translate();
        }
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

    protected function getOperation(mixed $value): OperationParams {
        $operation = null;

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
                throw reset($errors);
            }
        } else {
            throw new InvalidArgumentException('The `$value` is not a valid operation.');
        }

        return $operation;
    }

    protected function getDocumentNode(OperationParams $operation): DocumentNode {
        return $this->graphQL->parse($operation->query);
    }

    /**
     * @return array<ValidationRule>
     */
    protected function getValidationRules(): array {
        return (array) $this->rulesProvider->validationRules();
    }
}
