<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Model;
use App\Models\Reseller;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Schema\Type;
use Illuminate\Database\Eloquent\Builder;

class ResellerLoader extends CompanyLoader {
    use WithAssets;

    // <editor-fold desc="API">
    // =========================================================================
    protected function process(?Type $object): ?Model {
        // Process
        $company = null;

        try {
            $company = parent::process($object);

            if ($this->isWithAssets() && $company) {
                $this->loadAssets($company);
            }
        } finally {
            if ($company instanceof Reseller) {
                $this->updateResellerCalculatedProperties($company);
            }
        }

        // Return
        return $company;
    }

    protected function getCompanyFactory(): Factory {
        return $this->getResellersFactory();
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): OffsetBasedIterator {
        return $this->isWithAssetsDocuments()
            ? $this->client->getAssetsByResellerIdWithDocuments($owner->getKey())
            : $this->client->getAssetsByResellerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Reseller
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    //</editor-fold>
}
