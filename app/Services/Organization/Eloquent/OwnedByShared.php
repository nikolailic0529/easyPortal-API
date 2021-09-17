<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

/**
 * Marks that model can be shared across several Organizations.
 *
 * Model will be shared if value of
 * {@link \App\Services\Organization\Eloquent\OwnedByOrganization::getOrganizationColumn()}
 * is `null`.
 */
interface OwnedByShared {
    // empty
}
