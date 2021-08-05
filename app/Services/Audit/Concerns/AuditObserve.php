<?php declare(strict_types = 1);

namespace App\Services\Audit\Concerns;

use App\Services\Audit\Observers\AuditObserver;

/**
 * Adding observer to all models.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait AuditObserve {
    protected static function boot(): void {
        parent::boot();
        self::observe(AuditObserver::class);
    }
}
