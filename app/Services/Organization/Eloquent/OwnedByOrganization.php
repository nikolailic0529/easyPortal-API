<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use Illuminate\Database\Eloquent\Relations\Relation;

use function app;

/**
 * @mixin \App\Models\Model
 */
trait OwnedByOrganization {
    public static function bootOwnedByOrganization(): void {
        static::addGlobalScope(app()->make(OwnedByOrganizationScope::class));
    }

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('reseller_id');
    }

    public function getOrganizationThrough(): ?Relation {
        return null;
    }
}
