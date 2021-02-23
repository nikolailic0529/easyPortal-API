<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Oem;
use Illuminate\Support\Collection;

class OemProvider {
    /**
     * @var \Illuminate\Support\Collection<\App\Models\Oem>|null
     */
    protected Collection|null $oems = null;

    public function __construct() {
        // empty
    }

    public function get(string $abbr): Oem {
        return $this->getOems()->get($abbr)
            ?: $this->create($abbr);
    }

    protected function create(string $abbr): Oem {
        $oem       = new Oem();
        $oem->abbr = $abbr;
        $oem->name = $abbr;

        $oem->save();

        return $this->add($oem);
    }

    protected function add(Oem $oem): Oem {
        $this->getOems()->put($this->getKey($oem), $oem);

        return $oem;
    }

    /**
     * @return \Illuminate\Support\Collection<\App\Models\Oem>
     */
    protected function getOems(): Collection {
        if (!$this->oems) {
            $this->oems = Oem::query()->get()->keyBy(function (Oem $oem): string {
                return $this->getKey($oem);
            });
        }

        return $this->oems;
    }

    protected function getKey(Oem $oem): string {
        return $oem->abbr;
    }
}
