<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use App\Http\Controllers\Export\Utils\QueryOperation;
use App\Http\Controllers\Export\Utils\QueryOperationCache;
use App\Utils\Validation\Traits\WithData;
use App\Utils\Validation\Traits\WithValidator;
use Closure;
use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Executor\Values;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;

use function array_filter;
use function assert;
use function is_array;
use function is_string;
use function reset;

class Query implements InvokableRule, DataAwareRule, ValidatorAwareRule {
    use WithData;
    use WithValidator;

    public function __construct(
        protected Helper $helper,
        protected GraphQL $graphQL,
        protected SchemaBuilder $schemaBuilder,
        protected ProvidesValidationRules $rulesProvider,
    ) {
        // empty
    }

    // <editor-fold desc="InvokableRule">
    // =========================================================================
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
        $count         = 0;
        $queries       = 0;
        $fragments     = [];
        $operationNode = null;

        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                if ($definition->operation === 'query') {
                    $operationNode = $definition;
                }

                $queries++;
            }

            if ($definition instanceof FragmentDefinitionNode) {
                $fragments[$definition->name->value] = $definition;
            } else {
                $count++;
            }
        }

        if ($count > 1 || $queries !== 1 || !($operationNode instanceof OperationDefinitionNode)) {
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

            return;
        }

        // Save
        $validator = $this->getValidator();

        if ($validator) {
            QueryOperationCache::set($validator, new QueryOperation($operationNode, $fragments));
        }
    }
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param Closure(string): PotentiallyTranslatedString $fail
     * @param array<mixed>                                 $errors
     */
    protected function fail(Closure $fail, array $errors = []): void {
        $error = reset($errors);

        if ($error instanceof Exception && $error instanceof ClientAware && $error->isClientSafe()) {
            $fail($error->getMessage());
        } else {
            $fail('validation.http.controllers.export.query_invalid')->translate();
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
            $data      = (array) $this->getData();
            $operation = $this->createOperation([
                'query'         => $value,
                'variables'     => $data['variables'] ?? null,
                'operationName' => $data['operationName'] ?? null,
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
    //</editor-fold>
}
