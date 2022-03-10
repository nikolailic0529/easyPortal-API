<?php declare(strict_types = 1);

namespace App\Services\I18n\Eloquent;

use ArrayAccess;
use Generator;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

use function gettype;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * @implements \ArrayAccess<string, string>
 */
class TranslatedString implements Castable, Arrayable, ArrayAccess, IteratorAggregate, JsonSerializable {
    /**
     * @param array<string,string> $translations
     */
    public function __construct(
        protected array $translations = [],
    ) {
        // empty
    }

    // <editor-fold desc="IteratorAggregate">
    // =========================================================================
    /**
     * @return \Generator<string, array{locale: string, text: string}>
     */
    public function getIterator(): Generator {
        foreach ($this->translations as $locale => $text) {
            yield $locale => [
                'locale' => $locale,
                'text'   => $text,
            ];
        }
    }
    // </editor-fold>

    // <editor-fold desc="Arrayable">
    // =========================================================================
    /**
     * @return array<string,string>
     */
    public function toArray(): array {
        return $this->translations;
    }
    // </editor-fold>

    // <editor-fold desc="JsonSerializable">
    // =========================================================================
    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array {
        return $this->toArray();
    }
    // </editor-fold>

    // <editor-fold desc="Castable">
    // =========================================================================
    /**
     * @param array<string,mixed> $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes {
        return new class() implements CastsAttributes {
            /**
             * @inheritDoc
             */
            public function get($model, string $key, mixed $value, array $attributes): ?TranslatedString {
                if ($value === null || $value instanceof TranslatedString) {
                    // no action required
                } elseif (is_string($value)) {
                    $value = new TranslatedString(json_decode($value, true, flags: JSON_THROW_ON_ERROR));
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Type `%s` cannot be converted into `%s` instance.',
                        gettype($value),
                        TranslatedString::class,
                    ));
                }

                return $value;
            }

            /**
             * @inheritDoc
             */
            public function set($model, string $key, $value, array $attributes): mixed {
                if ($value !== null && !($value instanceof TranslatedString)) {
                    throw new InvalidArgumentException(sprintf(
                        'The `$value` should be instance of `%s`.',
                        TranslatedString::class,
                    ));
                }

                if ($value !== null) {
                    $value = $value->toArray();

                    ksort($value);

                    $value = $value
                        ? json_encode($value)
                        : null;
                }

                return [
                    $key => $value,
                ];
            }
        };
    }
    // </editor-fold>

    // <editor-fold desc="ArrayAccess">
    // =========================================================================
    public function offsetExists(mixed $offset): bool {
        return isset($this->translations[$offset]);
    }

    public function offsetGet(mixed $offset): string {
        return $this->translations[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->translations[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->translations[$offset]);
    }
    // </editor-fold>
}
