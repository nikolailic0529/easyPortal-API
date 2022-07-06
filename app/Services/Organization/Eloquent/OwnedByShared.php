<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

/**
 * Marks that model can be shared across several Organizations.
 *
 * Model will be shared if value of
 * {@link \App\Services\Organization\Eloquent\OwnedByOrganization::getOwnedByOrganizationColumn()}
 * is `null`.
 *
 * Be attention that current support is very limited:
 * - it doesn't work with relations (because query will be very slow)
 * - it doesn't work with Scout (because is not possible to create proper query)
 */
interface OwnedByShared {
    // empty
}
