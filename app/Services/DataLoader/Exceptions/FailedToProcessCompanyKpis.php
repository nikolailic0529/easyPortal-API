<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Customer;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use Throwable;

use function sprintf;

class FailedToProcessCompanyKpis extends FailedToProcessObject {
    public function __construct(
        protected Reseller|Customer|ResellerCustomer $model,
        protected CompanyKpis $kpi,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process CompanyKpis for %s `%s`.',
            $this->model->getMorphClass(),
            $this->model->getKey(),
        ), $previous);

        $this->setContext([
            'kpi' => $this->kpi,
        ]);
    }
}
