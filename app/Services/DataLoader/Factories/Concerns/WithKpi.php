<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithKpi {
    abstract protected function getNormalizer(): Normalizer;

    protected function kpi(Reseller|Customer $owner, ?CompanyKpis $kpis): ?Kpi {
        $kpi = null;

        if ($kpis) {
            $normalizer = $this->getNormalizer();
            $kpi        = ($owner->exists || $owner->relationLoaded('kpi') ? $owner->kpi : null) ?: new Kpi();

            $kpi->object                              = $owner;
            $kpi->assets_total                        = (int) $normalizer->number($kpis->totalAssets);
            $kpi->assets_active                       = (int) $normalizer->number($kpis->activeAssets);
            $kpi->assets_active_percent               = (float) $normalizer->number($kpis->activeAssetsPercentage);
            $kpi->assets_active_on_contract           = (int) $normalizer->number($kpis->activeAssetsOnContract);
            $kpi->assets_active_on_warranty           = (int) $normalizer->number($kpis->activeAssetsOnWarranty);
            $kpi->assets_active_exposed               = (int) $normalizer->number($kpis->activeExposedAssets);
            $kpi->customers_active                    = (int) $normalizer->number($kpis->activeCustomers);
            $kpi->customers_active_new                = (int) $normalizer->number($kpis->newActiveCustomers);
            $kpi->contracts_active                    = (int) $normalizer->number($kpis->activeContracts);
            $kpi->contracts_active_amount             = (float) $normalizer->number($kpis->activeContractTotalAmount);
            $kpi->contracts_active_new                = (int) $normalizer->number($kpis->newActiveContracts);
            $kpi->contracts_expiring                  = (int) $normalizer->number($kpis->expiringContracts);
            $kpi->contracts_expired                   = (int) $normalizer->number($kpis->expiredContracts);
            $kpi->quotes_active                       = (int) $normalizer->number($kpis->activeQuotes);
            $kpi->quotes_active_amount                = (float) $normalizer->number($kpis->activeQuotesTotalAmount);
            $kpi->quotes_active_new                   = (int) $normalizer->number($kpis->newActiveQuotes);
            $kpi->quotes_expiring                     = (int) $normalizer->number($kpis->expiringQuotes);
            $kpi->quotes_expired                      = (int) $normalizer->number($kpis->expiredQuotes);
            $kpi->quotes_ordered                      = (int) $normalizer->number($kpis->orderedQuotes);
            $kpi->quotes_accepted                     = (int) $normalizer->number($kpis->acceptedQuotes);
            $kpi->quotes_requested                    = (int) $normalizer->number($kpis->requestedQuotes);
            $kpi->quotes_received                     = (int) $normalizer->number($kpis->receivedQuotes);
            $kpi->quotes_rejected                     = (int) $normalizer->number($kpis->rejectedQuotes);
            $kpi->quotes_awaiting                     = (int) $normalizer->number($kpis->awaitingQuotes);
            $kpi->service_revenue_total_amount        = (float) $normalizer->number($kpis->serviceRevenueTotalAmount);
            $kpi->service_revenue_total_amount_change = (float) $normalizer->number(
                $kpis->serviceRevenueTotalAmountChange,
            );

            $kpi->save();
        }

        return $kpi;
    }
}
