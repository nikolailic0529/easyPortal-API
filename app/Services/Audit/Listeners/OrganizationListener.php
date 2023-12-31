<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Services\Audit\Enums\Action;
use App\Services\Organization\Events\OrganizationChanged;

class OrganizationListener extends Listener {
    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            OrganizationChanged::class,
        ];
    }

    public function __invoke(OrganizationChanged $event): void {
        // We create two records, one for current and one for previous
        // organization. They are required to show information for both
        // organizations.

        // Previous
        $previous = $event->getPrevious();

        if ($previous) {
            $this->auditor->create($previous, Action::orgChanged());
        }

        // Current
        $current = $event->getCurrent();

        if ($current) {
            $this->auditor->create($current, Action::orgChanged(), $current);
        }
    }
}
