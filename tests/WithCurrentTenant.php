<?php declare(strict_types = 1);

namespace Tests;

use App\CurrentTenant;
use App\Models\Organization;

/**
 * @deprecated Not sure that this trait is needed, probably you want {@link \Tests\WithTenant}.
 *
 * @mixin \Tests\TestCase
 */
trait WithCurrentTenant {
    public function setUpWithCurrentTenant(): void {
        $this->app->bind(CurrentTenant::class, static function (): CurrentTenant {
            return (new CurrentTenant())->set(
                Organization::query()->first() ?: Organization::factory()->create(),
            );
        });
    }

    public function tearDownWithCurrentTenant(): void {
        unset($this->app[CurrentTenant::class]);
    }
}
