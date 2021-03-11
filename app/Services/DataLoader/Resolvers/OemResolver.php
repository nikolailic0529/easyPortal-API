<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OemResolver extends Resolver {
    public function get(string $abbr, Closure $factory = null): ?Oem {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($abbr, $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return Oem::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'abbr' => new ClosureKey(static function (Oem $oem): string {
                return $oem->abbr;
            }),
        ];
    }
}
