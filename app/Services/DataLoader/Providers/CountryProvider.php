<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Country;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CountryProvider extends Provider {
    public function get(string $code, Closure $factory): Country {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return Country::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'code' => new ClosureKey(static function (Country $Country): string {
                return $Country->code;
            }),
        ];
    }
}
