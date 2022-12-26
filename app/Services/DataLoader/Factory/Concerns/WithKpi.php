<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyKpis;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @mixin Factory
 */
trait WithKpi {
    abstract protected function getExceptionHandler(): ExceptionHandler;

    protected function kpi(Reseller|Customer|ResellerCustomer $owner, ?CompanyKpis $kpis): ?Kpi {
        $kpi = null;

        if ($kpis) {
            $kpi    = ($owner->exists || $owner->relationLoaded('kpi') ? $owner->kpi : null) ?: new Kpi();
            $exists = $kpi->exists;

            try {
                $kpi->assets_total                        = $kpis->totalAssets ?? 0;
                $kpi->assets_active                       = $kpis->activeAssets ?? 0;
                $kpi->assets_active_percent               = $kpis->activeAssetsPercentage ?? 0;
                $kpi->assets_active_on_contract           = $kpis->activeAssetsOnContract ?? 0;
                $kpi->assets_active_on_warranty           = $kpis->activeAssetsOnWarranty ?? 0;
                $kpi->assets_active_exposed               = $kpis->activeExposedAssets ?? 0;
                $kpi->customers_active                    = $kpis->activeCustomers ?? 0;
                $kpi->customers_active_new                = $kpis->newActiveCustomers ?? 0;
                $kpi->contracts_active                    = $kpis->activeContracts ?? 0;
                $kpi->contracts_active_amount             = $kpis->activeContractTotalAmount ?? 0;
                $kpi->contracts_active_new                = $kpis->newActiveContracts ?? 0;
                $kpi->contracts_expiring                  = $kpis->expiringContracts ?? 0;
                $kpi->contracts_expired                   = $kpis->expiredContracts ?? 0;
                $kpi->quotes_active                       = $kpis->activeQuotes ?? 0;
                $kpi->quotes_active_amount                = $kpis->activeQuotesTotalAmount ?? 0;
                $kpi->quotes_active_new                   = $kpis->newActiveQuotes ?? 0;
                $kpi->quotes_expiring                     = $kpis->expiringQuotes ?? 0;
                $kpi->quotes_expired                      = $kpis->expiredQuotes ?? 0;
                $kpi->quotes_ordered                      = $kpis->orderedQuotes ?? 0;
                $kpi->quotes_accepted                     = $kpis->acceptedQuotes ?? 0;
                $kpi->quotes_requested                    = $kpis->requestedQuotes ?? 0;
                $kpi->quotes_received                     = $kpis->receivedQuotes ?? 0;
                $kpi->quotes_rejected                     = $kpis->rejectedQuotes ?? 0;
                $kpi->quotes_awaiting                     = $kpis->awaitingQuotes ?? 0;
                $kpi->service_revenue_total_amount        = $kpis->serviceRevenueTotalAmount ?? 0.0;
                $kpi->service_revenue_total_amount_change = $kpis->serviceRevenueTotalAmountChange ?? 0.0;

                $kpi->save();
            } catch (Exception $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessCompanyKpis($owner, $kpis, $exception),
                );

                if (!$exists) {
                    $kpi = null;
                }
            }
        }

        return $kpi;
    }
}
