<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Services\Audit\Auditor;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Providers\EventsProvider;

abstract class Listener implements EventsProvider {
    public function __construct(
        protected CurrentOrganization $org,
        protected Auditor $auditor,
    ) {
        // empty
    }
}
