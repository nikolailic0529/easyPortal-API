<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Services\Audit\Auditor;
use App\Services\Organization\CurrentOrganization;

abstract class Listener implements Subscriber {
    public function __construct(
        protected Auditor $auditor,
        protected CurrentOrganization $org,
    ) {
        // empty
    }
}
