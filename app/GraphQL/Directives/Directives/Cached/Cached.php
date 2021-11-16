<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\GraphQL\Service;
use App\Services\Organization\CurrentOrganization;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\RootType;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_slice;
use function count;
use function implode;
use function in_array;
use function sprintf;

class Cached extends BaseDirective implements FieldMiddleware {
    public function __construct(
        protected Repository $cache,
        protected Service $service,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Cache the resolved value of a field.
            """
            directive @cached on FIELD_DEFINITION
            GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
        $fieldValue = $next($fieldValue);
        $resolver   = $fieldValue->getResolver();

        $fieldValue->setResolver(
            function (
                mixed $root,
                array $args,
                GraphQLContext $context,
                ResolveInfo $resolveInfo,
            ) use (
                $resolver,
            ): mixed {
                // Cached?
                $key              = $this->getCacheKey($root, $args, $context, $resolveInfo);
                [$cached, $value] = $this->getCachedValue($key);

                if ($cached) {
                    return $value;
                }

                // Resolve value and store it in the cache
                $value = $this->resolve($key, $resolver, $root, $args, $context, $resolveInfo);
                $value = $this->setCachedValue($key, $value);

                // Return
                return $value;
            },
        );

        return $fieldValue;
    }

    /**
     * @param array<mixed> $args
     */
    protected function getCacheKey(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        // Root
        $cacheable = true;
        $key       = [
            $this->organization,
        ];

        if ($root instanceof Model) {
            $key[] = $root;
        } elseif ($root instanceof ParentValue) {
            $parent    = $root->getRoot();
            $key[]     = $parent instanceof Model ? $parent : '';
            $key[]     = $root->getResolveInfo()->fieldName;
            $key[]     = $root->getArgs() ?: '';
            $path      = array_slice($resolveInfo->path, count($root->getResolveInfo()->path));
            $cacheable = count($path) === 1;
        } elseif ($root === null) {
            $key[] = '';
        } else {
            $cacheable = false;
        }

        if (!$cacheable) {
            throw new InvalidArgumentException(sprintf(
                'Property `%s.%s` by the path `%s` cannot be cached.',
                $resolveInfo->parentType->name,
                $resolveInfo->fieldName,
                implode('.', $resolveInfo->path),
            ));
        }

        // Field
        $key[] = $resolveInfo->fieldName;
        $key[] = $args ?: '';

        // Self
        $key[] = $this;

        // Return
        return $key;
    }

    /**
     * @return array{bool,mixed}
     */
    protected function getCachedValue(mixed $key): array {
        $cached = false;
        $value  = $this->service->get($key, static function (mixed $value) use (&$cached): mixed {
            // Small trick to determine if the value exists in the cache or not.
            $cached = true;

            return $value;
        });

        return [$cached, $value];
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    protected function setCachedValue(mixed $key, mixed $value): mixed {
        return $this->service->set($key, $value);
    }

    protected function isRoot(ResolveInfo $resolveInfo): bool {
        $type  = $resolveInfo->parentType->name;
        $roots = [
            RootType::QUERY,
            'Application',
        ];

        return in_array($type, $roots, true);
    }

    /**
     * @param array<mixed> $args
     */
    protected function resolve(
        mixed $key,
        callable $resolver,
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        return $resolver($root, $args, $context, $resolveInfo);
    }
}
