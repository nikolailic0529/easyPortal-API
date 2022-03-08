<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyKpis;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithKpi {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getExceptionHandler(): ExceptionHandler;

    protected function kpi(Reseller|Customer|ResellerCustomer $owner, ?CompanyKpis $kpis): ?Kpi {
        $kpi = null;

        if ($kpis) {
            $normalizer = $this->getNormalizer();
            $kpi        = ($owner->exists || $owner->relationLoaded('kpi') ? $owner->kpi : null) ?: new Kpi();

            try {
                $kpi->assets_total                        = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->totalAssets ?? null,
                ));
                $kpi->assets_active                       = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeAssets ?? null,
                ));
                $kpi->assets_active_percent               = (float) $normalizer->unsigned($normalizer->float(
                    $kpis->activeAssetsPercentage ?? null,
                ));
                $kpi->assets_active_on_contract           = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeAssetsOnContract ?? null,
                ));
                $kpi->assets_active_on_warranty           = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeAssetsOnWarranty ?? null,
                ));
                $kpi->assets_active_exposed               = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeExposedAssets ?? null,
                ));
                $kpi->customers_active                    = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeCustomers ?? null,
                ));
                $kpi->customers_active_new                = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->newActiveCustomers ?? null,
                ));
                $kpi->contracts_active                    = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeContracts ?? null,
                ));
                $kpi->contracts_active_amount             = (float) $normalizer->unsigned($normalizer->float(
                    $kpis->activeContractTotalAmount ?? null,
                ));
                $kpi->contracts_active_new                = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->newActiveContracts ?? null,
                ));
                $kpi->contracts_expiring                  = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->expiringContracts ?? null,
                ));
                $kpi->contracts_expired                   = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->expiredContracts ?? null,
                ));
                $kpi->quotes_active                       = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->activeQuotes ?? null,
                ));
                $kpi->quotes_active_amount                = (float) $normalizer->unsigned($normalizer->float(
                    $kpis->activeQuotesTotalAmount ?? null,
                ));
                $kpi->quotes_active_new                   = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->newActiveQuotes ?? null,
                ));
                $kpi->quotes_expiring                     = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->expiringQuotes ?? null,
                ));
                $kpi->quotes_expired                      = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->expiredQuotes ?? null,
                ));
                $kpi->quotes_ordered                      = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->orderedQuotes ?? null,
                ));
                $kpi->quotes_accepted                     = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->acceptedQuotes ?? null,
                ));
                $kpi->quotes_requested                    = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->requestedQuotes ?? null,
                ));
                $kpi->quotes_received                     = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->receivedQuotes ?? null,
                ));
                $kpi->quotes_rejected                     = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->rejectedQuotes ?? null,
                ));
                $kpi->quotes_awaiting                     = (int) $normalizer->unsigned($normalizer->int(
                    $kpis->awaitingQuotes ?? null,
                ));
                $kpi->service_revenue_total_amount        = (float) $normalizer->unsigned($normalizer->float(
                    $kpis->serviceRevenueTotalAmount ?? null,
                ));
                $kpi->service_revenue_total_amount_change = (float) $normalizer->float(
                    $kpis->serviceRevenueTotalAmountChange ?? null,
                );

                $kpi->save();
            } catch (Exception $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessCompanyKpis($owner, $kpis, $exception),
                );

                if (!$kpi->exists) {
                    $kpi = null;
                }
            }
        }

        return $kpi;
    }
}
