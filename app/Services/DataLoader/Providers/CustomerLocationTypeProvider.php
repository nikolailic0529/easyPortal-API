<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\CustomerLocationType;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CustomerLocationTypeProvider extends Provider {
    public function get(string $type, Closure $factory): CustomerLocationType {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($type, $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return CustomerLocationType::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
                'type' => new ClosureKey(static function (CustomerLocationType $oem): string {
                    return $oem->type;
                }),
            ] + parent::getKeyRetrievers();
    }
}
