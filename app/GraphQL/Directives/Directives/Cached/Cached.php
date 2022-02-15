<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\GraphQL\Cache;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_slice;
use function count;
use function implode;
use function mb_strtolower;
use function microtime;
use function sprintf;

/**
 * @see /docs/Application-GraphQL-Cache-Settings.md
 */
class Cached extends BaseDirective implements FieldMiddleware {
    public function __construct(
        protected Cache $cache,
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
                return $this->resolve($resolver, $root, $args, $context, $resolveInfo);
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
        $key       = [];

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
     * @return array{bool,bool,mixed}
     */
    protected function getCachedValue(mixed $key): array {
        $expired = false;
        $cached  = false;
        $value   = $this->cache->get($key);

        if ($value instanceof CachedValue) {
            $expired = $this->cache->isExpired($value->created, $value->expired);
            $cached  = true;
            $value   = $value->value;
        } elseif ($value !== null) {
            $expired = true;
            $cached  = true;
        } else {
            // empty
        }

        return [$cached, $expired, $value];
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    protected function setCachedValue(mixed $key, mixed $value): mixed {
        $created = Date::now();
        $expired = Date::now()->add($this->cache->getTtl());
        $cached  = new CachedValue($created, $expired, $value);

        $this->cache->set($key, $cached);

        return $value;
    }

    /**
     * @param array<mixed> $args
     */
    protected function resolve(
        callable $resolver,
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        // Cached?
        $key                        = $this->getCacheKey($root, $args, $context, $resolveInfo);
        [$cached, $expired, $value] = $this->getCachedValue($key);

        if ($cached && !$expired) {
            return $value;
        }

        // Resolve value
        if ($this->getResolveMode($root) === CachedMode::lock()) {
            // If we have cached value and lock exist (=another request
            // started to run the resolver already) we can just return
            // the cached value
            if (!$cached || !$this->resolveIsLocked($key)) {
                $value = $this->resolveWithLock($key, $resolver, $root, $args, $context, $resolveInfo);
            }
        } else {
            $value = $this->resolveWithThreshold($key, $resolver, $root, $args, $context, $resolveInfo);
        }

        // Return
        return $value;
    }

    protected function resolveIsLocked(mixed $key): bool {
        return $this->cache->isLocked($key);
    }

    /**
     * @param array<mixed> $args
     */
    protected function resolveWithThreshold(
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

        if ($this->cache->isSlowQuery($time)) {
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
        return $this->setCachedValue($key, $this->cache->lock(
            $key,
            function () use ($key, $resolver, $root, $args, $context, $resolveInfo): mixed {
                // Value can be already resolved in another process/request
                [$cached, $expired, $value] = $this->getCachedValue($key);

                if (!$cached || $expired) {
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
