<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\GraphQL\Directives\BuilderArguments;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

// TODO [GraphQL] Not sure about how good this name... It was chosen because
//      the directive was created specially for `*Aggregated` fields.

abstract class Aggregated extends BaseDirective implements FieldResolver {
    use BuilderArguments;

    public static function definition(): string {
        $arguments = static::getArgumentsDefinition();

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            Gets the current builder and passed it to children. At least one argument required.
            """
            directive @aggregated(
                {$arguments}

                """
                Specify the relation name to use.
                """
                relation: String
            ) on FIELD_DEFINITION
            GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): BuilderValue {
                return new BuilderValue(
                    $root,
                    $args,
                    $context,
                    $resolveInfo,
                    $this->getBuilder($root, $args, $context, $resolveInfo),
                );
            },
        );
    }

    protected function allowGuessBuilder(): bool {
        return false;
    }
}
