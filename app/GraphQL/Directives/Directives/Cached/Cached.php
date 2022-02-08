<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\GraphQL\Service;
use App\Services\I18n\Locale;
use App\Services\Organization\CurrentOrganization;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Throwable;

use function array_slice;
use function count;
use function implode;
use function mb_strtolower;
use function microtime;
use function serialize;
use function sprintf;
use function unserialize;

class Cached extends BaseDirective implements FieldMiddleware {
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected Service $service,
        protected Locale $locale,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Cache the resolved value of a field.
            """
            directive @cached(
                mode: CachedMode
            ) on FIELD_DEFINITION
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

                // Resolve value
                if ($this->getResolveMode($root) === CachedMode::lock()) {
                    $value = $this->resolveWithLock($key, $resolver, $root, $args, $context, $resolveInfo);
                } else {
                    $value = $this->resolve($key, $resolver, $root, $args, $context, $resolveInfo);
                }

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
            $this->locale,
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

            // If `unserialize()` fail it is not critical and should not break
            // the query.
            try {
                $value = unserialize($value);
            } catch (Throwable) {
                $cached = false;
                $value  = null;
            }

            // CachedValue?
            if ($value instanceof CachedValue) {
                $value = $value->value;
            }

            // Return
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
        try {
            $this->service->set($key, serialize(new CachedValue(Date::now(), $value)));
        } catch (Throwable $exception) {
            $this->exceptionHandler->report($exception);
        }

        return $value;
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
        $begin = microtime(true);
        $value = $resolver($root, $args, $context, $resolveInfo);
        $time  = microtime(true) - $begin;

        if ($this->service->isSlowQuery($time)) {
            $value = $this->setCachedValue($key, $value);
        }

        return $value;
    }

    /**
     * @param array<mixed> $args
     */
    protected function resolveWithLock(
        mixed $key,
        callable $resolver,
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        return $this->setCachedValue($key, $this->service->lock(
            $key,
            function () use ($key, $resolver, $root, $args, $context, $resolveInfo): mixed {
                // Value can be already resolved in another process/request
                [$cached, $value] = $this->getCachedValue($key);

                if (!$cached) {
                    $value = $resolver($root, $args, $context, $resolveInfo);
                }

                return $value;
            },
        ));
    }

    protected function getResolveMode(mixed $root): CachedMode {
        $arg  = $this->directiveArgValue('mode');
        $mode = !$arg
            ? ($this->isRootQuery($root) ? CachedMode::lock() : CachedMode::threshold())
            : CachedMode::get(mb_strtolower($arg));

        return $mode;
    }

    protected function isRootQuery(mixed $root): bool {
        if ($root instanceof ParentValue) {
            $root = $root->getRoot();
        }

        return $root === null;
    }
}
