<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use JsonSerializable;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

class Statistics implements JsonSerializable, Castable {
    /**
     * @param array<string,int> $data
     */
    public function __construct(
        protected array $data = [],
    ) {
        // empty
    }

    public function __get(string $name): ?int {
        return Arr::get($this->data, $name);
    }

    public function __set(string $name, int $value): void {
        Arr::set($this->data, $name, $value);
    }

    public function jsonSerialize(): mixed {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public static function castUsing(array $arguments): CastsAttributes {
        return new class() implements CastsAttributes {
            /**
             * @inheritDoc
             */
            public function get($model, string $key, mixed $value, array $attributes): ?Statistics {
                $statistics = null;

                if (is_string($value)) {
                    $statistics = new Statistics(json_decode($value, true));
                } elseif (is_array($value)) {
                    $statistics = new Statistics($value);
                } elseif ($statistics !== null) {
                    throw new InvalidArgumentException(sprintf(
                        'The `$value` cannot be converted into `%s`.',
                        Statistics::class,
                    ));
                } else {
                    // empty
                }

                return $statistics;
            }

            /**
             * @inheritDoc
             */
            public function set($model, string $key, $value, array $attributes): mixed {
                return [
                    $key => $value !== null ? json_encode($value) : null,
                ];
            }
        };
    }
}
