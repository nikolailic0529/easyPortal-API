<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonSerializable;

use function json_encode;

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
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, int $value): void {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool {
        return isset($this->data[$name]);
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
            public function get($model, string $key, mixed $value, array $attributes): Statistics {
                return new Statistics($value);
            }

            /**
             * @inheritDoc
             */
            public function set($model, string $key, $value, array $attributes): mixed {
                return [$key => json_encode($value)];
            }
        };
    }
}
