<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Country;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Illuminate\Database\Eloquent\Builder;

use function mb_strtoupper;

class CountryProvider extends Provider {
    public function get(string $code, string $name): Country {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, function () use ($code, $name): Model {
            return $this->create($code, $name);
        });
    }

    protected function create(string $code, string $name): Country {
        $country       = new Country();
        $country->code = mb_strtoupper($this->normalizer->string($code));
        $country->name = $this->normalizer->string($name);

        $country->save();

        return $country;
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
            ] + parent::getKeyRetrievers();
    }
}
