<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\Oem;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Provider;
use Illuminate\Database\Eloquent\Builder;

class OemProvider extends Provider {
    public function get(string $abbr): Oem {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($abbr, function () use ($abbr): Oem {
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
                'abbr' => new class() implements KeyRetriever {
                    public function get(Model $model): string|int {
                        /** @var \App\Models\Oem $model */
                        return $model->abbr;
                    }
                },
            ] + parent::getKeyRetrievers();
    }
}
