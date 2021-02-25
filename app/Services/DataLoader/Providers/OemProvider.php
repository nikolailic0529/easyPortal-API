<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\Oem;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Illuminate\Database\Eloquent\Builder;

class OemProvider extends Provider {
    public function get(string $abbr): Oem {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($abbr, function () use ($abbr): Model {
            return $this->create($abbr);
        });
    }

    protected function create(string $abbr): Oem {
        $abbr      = $this->normalizer->string($abbr);
        $oem       = new Oem();
        $oem->abbr = $abbr;
        $oem->name = $abbr;

        $oem->save();

        return $oem;
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
            ] + parent::getKeyRetrievers();
    }
}
