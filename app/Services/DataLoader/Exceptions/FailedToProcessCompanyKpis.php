<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\CompanyKpis;
use App\Utils\Eloquent\Model;
use Throwable;

use function sprintf;

class FailedToProcessCompanyKpis extends FailedToProcessObject {
    public function __construct(
        protected Model $model,
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
