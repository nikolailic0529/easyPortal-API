<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithKpi {
    abstract protected function getNormalizer(): Normalizer;

    protected function kpi(Reseller|Customer|ResellerCustomer $owner, ?CompanyKpis $kpis): ?Kpi {
        $kpi = null;

        if ($kpis) {
            $normalizer = $this->getNormalizer();
            $kpi        = ($owner->exists || $owner->relationLoaded('kpi') ? $owner->kpi : null) ?: new Kpi();

            $kpi->assets_total                        = (int) $normalizer->number($kpis->totalAssets ?? null);
            $kpi->assets_active                       = (int) $normalizer->number($kpis->activeAssets ?? null);
            $kpi->assets_active_percent               = (float) $normalizer->number(
                $kpis->activeAssetsPercentage ?? null,
            );
            $kpi->assets_active_on_contract           = (int) $normalizer->number(
                $kpis->activeAssetsOnContract ?? null,
            );
            $kpi->assets_active_on_warranty           = (int) $normalizer->number(
                $kpis->activeAssetsOnWarranty ?? null,
            );
            $kpi->assets_active_exposed               = (int) $normalizer->number($kpis->activeExposedAssets ?? null);
            $kpi->customers_active                    = (int) $normalizer->number($kpis->activeCustomers ?? null);
            $kpi->customers_active_new                = (int) $normalizer->number($kpis->newActiveCustomers ?? null);
            $kpi->contracts_active                    = (int) $normalizer->number($kpis->activeContracts ?? null);
            $kpi->contracts_active_amount             = (float) $normalizer->number(
                $kpis->activeContractTotalAmount ?? null,
            );
            $kpi->contracts_active_new                = (int) $normalizer->number($kpis->newActiveContracts ?? null);
            $kpi->contracts_expiring                  = (int) $normalizer->number($kpis->expiringContracts ?? null);
            $kpi->contracts_expired                   = (int) $normalizer->number($kpis->expiredContracts ?? null);
            $kpi->quotes_active                       = (int) $normalizer->number($kpis->activeQuotes ?? null);
            $kpi->quotes_active_amount                = (float) $normalizer->number(
                $kpis->activeQuotesTotalAmount ?? null,
            );
            $kpi->quotes_active_new                   = (int) $normalizer->number($kpis->newActiveQuotes ?? null);
            $kpi->quotes_expiring                     = (int) $normalizer->number($kpis->expiringQuotes ?? null);
            $kpi->quotes_expired                      = (int) $normalizer->number($kpis->expiredQuotes ?? null);
            $kpi->quotes_ordered                      = (int) $normalizer->number($kpis->orderedQuotes ?? null);
            $kpi->quotes_accepted                     = (int) $normalizer->number($kpis->acceptedQuotes ?? null);
            $kpi->quotes_requested                    = (int) $normalizer->number($kpis->requestedQuotes ?? null);
            $kpi->quotes_received                     = (int) $normalizer->number($kpis->receivedQuotes ?? null);
            $kpi->quotes_rejected                     = (int) $normalizer->number($kpis->rejectedQuotes ?? null);
            $kpi->quotes_awaiting                     = (int) $normalizer->number($kpis->awaitingQuotes ?? null);
            $kpi->service_revenue_total_amount        = (float) $normalizer->number(
                $kpis->serviceRevenueTotalAmount ?? null,
            );
            $kpi->service_revenue_total_amount_change = (float) $normalizer->number(
                $kpis->serviceRevenueTotalAmountChange ?? null,
            );

            $kpi->save();
        }

        return $kpi;
    }
}
