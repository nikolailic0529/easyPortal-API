<?php declare(strict_types = 1);

namespace App\Utils\Cache;

use App\Services\I18n\Locale;
use App\Services\Organization\OrganizationProvider;
use App\Services\Queue\NamedJob;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use League\Geotools\Geohash\Geohash;
use League\Geotools\Geotools;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Stringable;
use Traversable;

use function implode;
use function is_array;
use function is_int;
use function is_null;
use function is_scalar;
use function is_string;
use function json_encode;
use function ksort;
use function sha1;
use function sort;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const SORT_REGULAR;

class CacheKey implements Stringable {
    /**
     * @var array<string>
     */
    protected array $key;

    /**
     * @param array<mixed> $key
     */
    public function __construct(array $key) {
        $this->key = $this->normalize($key);
    }

    public function __toString(): string {
        return $this->join($this->key);
    }

    /**
     * @param array<mixed> $key
     *
     * @return array<string>
     */
    protected function normalize(array $key): array {
        $normalized = [];

        foreach ($key as $k => $value) {
            $value = $this->value($value);

            if (is_array($value)) {
                $value = $this->hash($this->encode($value));
            } elseif (is_string($value)) {
                // as is
            } elseif (is_null($value)) {
                $value = '';
            } else {
                throw new CacheKeyInvalidValue($value);
            }

            $normalized[$k] = $value;
        }

        return $normalized;
    }

    protected function value(mixed $value): mixed {
        $normalized = null;

        if (is_array($value) || $value instanceof Traversable) {
            $isList     = true;
            $normalized = [];

            foreach ($value as $k => $v) {
                $isList         = $isList && is_int($k);
                $normalized[$k] = $this->value($v);
            }

            if ($isList) {
                sort($normalized, SORT_REGULAR);
            } else {
                ksort($normalized);
            }
        } elseif ($value instanceof Model) {
            if (!$value->exists || !$value->getKey()) {
                throw new CacheKeyInvalidModel($value);
            }

            $normalized = $this->join([$value->getMorphClass(), (string) $value->getKey()]);
        } elseif ($value instanceof QueueableEntity) {
            $normalized = $this->join([
                $value::class,
                $value->getQueueableConnection(),
                $value->getQueueableId(),
            ]);
        } elseif ($value instanceof NamedJob) {
            $normalized = $value->displayName();
        } elseif ($value instanceof OrganizationProvider) {
            if ($value->defined()) {
                $normalized = $this->join([
                    $value->get()->getMorphClass(),
                    $value->isRoot()
                        ? '00000000-0000-0000-0000-000000000000'
                        : $value->getKey(),
                ]);
            }
        } elseif ($value instanceof Locale) {
            $normalized = $value->get();
        } elseif ($value instanceof BaseDirective) {
            $normalized = "@{$value->name()}";
        } elseif ($value instanceof Geohash) {
            $normalized = $value->getGeohash()
                ?: (new Geotools())->geohash()->encode($value->getCoordinate())->getGeohash();
        } elseif ($value instanceof CacheKeyable) {
            $normalized = $value::class;
        } elseif ($value instanceof JsonSerializable) {
            $normalized = $value;
        } elseif (is_scalar($value)) {
            $normalized = $value;
        } elseif (is_null($value)) {
            $normalized = $value;
        } else {
            throw new CacheKeyInvalidValue($value);
        }

        return $normalized;
    }

    protected function hash(string $value): string {
        return sha1($value);
    }

    protected function encode(mixed $value): string {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_LINE_TERMINATORS,
        );
    }

    /**
     * @param array<string> $parts
     */
    protected function join(array $parts): string {
        return implode(':', $parts);
    }
}
