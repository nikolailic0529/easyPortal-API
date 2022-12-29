<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\GraphQL\Cache;
use App\Utils\Cache\CacheKey;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_slice;
use function count;
use function implode;
use function is_string;
use function mb_strtolower;
use function microtime;
use function sprintf;

/**
 * @see /docs/Application/GraphQL-Cache.md
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

        if ($this->cache->isEnabled()) {
            $resolver = $fieldValue->getResolver();

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
        }

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
    ): CacheKey {
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
        return new CacheKey($key);
    }

    protected function getCachedValue(mixed $key): ?CachedValue {
        $value = $this->cache->get($key);
        $value = $value instanceof CachedValue
            ? $value
            : null;

        return $value;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    protected function setCachedValue(mixed $key, mixed $value, float $time): mixed {
        $created = Date::now();
        $expired = Date::now()->add($this->cache->getTtl());
        $cached  = new CachedValue($created, $expired, $time, $value);

        $this->cache->set($key, $cached);

        return $value;
    }

    protected function deleteCachedValue(mixed $key): bool {
        return $this->cache->delete($key);
    }

    /**
     * @param Callable(mixed, array<mixed>, GraphQLContext, ResolveInfo):mixed $resolver
     * @param array<mixed>                                                     $args
     */
    protected function resolve(
        callable $resolver,
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        // Cached?
        $key     = $this->getCacheKey($root, $args, $context, $resolveInfo);
        $cached  = $this->getCachedValue($key);
        $expired = $cached && $this->cache->isExpired($cached->created, $cached->expired);

        if ($cached && !$expired) {
            return $cached->value;
        }

        // If we have cached value and lock exist (=another request started to
        // run the resolver already) we can just return the cached value
        $mode     = $this->getResolveMode();
        $lockable = $cached
            ? $this->cache->isQueryLockable($cached->time ?? null)
            : $mode === CachedMode::normal();

        if ($cached && $lockable && $this->cache->isLocked($key)) {
            return $cached->value;
        }

        // Resolve
        $time    = null;
        $value   = null;
        $resolve = static function () use (&$time, $resolver, $root, $args, $context, $resolveInfo): mixed {
            $begin = microtime(true);
            $value = $resolver($root, $args, $context, $resolveInfo);
            $time  = microtime(true) - $begin;

            return $value;
        };

        if ($lockable) {
            $start = microtime(true);
            $value = $this->cache->lock(
                $key,
                function () use ($start, $key, $resolve): mixed {
                    // Value may be already resolved in another process/request
                    // so we can reuse it (if it was really locked of course).
                    $cached = $this->cache->isQueryWasLocked(microtime(true) - $start)
                        ? $this->getCachedValue($key)
                        : null;
                    $value  = $cached && !$this->cache->isExpired($cached->created, $cached->expired)
                        ? $cached->value
                        : $resolve();

                    return $value;
                },
            );
        } else {
            $value = $resolve();
        }

        // Save if it was resolved in the current process/request
        if ($time !== null) {
            if ($mode === CachedMode::normal() || $this->cache->isQuerySlow($time)) {
                $value = $this->setCachedValue($key, $value, $time);
            } elseif ($expired && $mode === CachedMode::threshold()) {
                $this->deleteCachedValue($key);
            } else {
                // empty
            }
        }

        // Return
        return $value;
    }

    protected function getResolveMode(): CachedMode {
        $mode = $this->directiveArgValue('mode');
        $mode = is_string($mode) && $mode
            ? CachedMode::get(mb_strtolower($mode))
            : CachedMode::normal();

        return $mode;
    }
}
