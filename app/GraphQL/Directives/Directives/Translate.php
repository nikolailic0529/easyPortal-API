<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\Services\I18n\Contracts\Translatable;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Factories\FieldFactory;
use Nuwave\Lighthouse\Schema\RootType;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

abstract class Translate extends BaseDirective implements FieldResolver {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Translates Model/Object property.

            Model/Object must implements App\\Services\\I18n\\Contracts\\Translatable.
            """
            directive @translate on FIELD_DEFINITION
            GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        // Subscription?
        if ($fieldValue->getParentName() === RootType::SUBSCRIPTION) {
            return $fieldValue;
        }

        // Set Resolver
        $default  = FieldFactory::defaultResolver($fieldValue);
        $resolver = static function (
            mixed $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo,
        ) use (
            $default,
        ) {
            return $root instanceof Translatable
                ? $root->getTranslatedProperty($resolveInfo->fieldName)
                : $default($root, $args, $context, $resolveInfo);
        };

        // Return
        return $fieldValue->setResolver($resolver);
    }
}
