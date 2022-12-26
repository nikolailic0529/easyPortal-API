<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Events;

use App\Models\Reseller;
use App\Services\DataLoader\Schema\Types\Company;
use Illuminate\Queue\SerializesModels;

class ResellerUpdated {
    use SerializesModels;

    public function __construct(
        protected Reseller $reseller,
        protected Company $company,
    ) {
        // empty
    }

    public function getReseller(): Reseller {
        return $this->reseller;
    }

    public function getCompany(): Company {
        return $this->company;
    }
}
