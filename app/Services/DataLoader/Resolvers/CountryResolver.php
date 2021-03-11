<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Country;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CountryResolver extends Resolver {
    public function get(string $code, Closure $factory = null): ?Country {
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
