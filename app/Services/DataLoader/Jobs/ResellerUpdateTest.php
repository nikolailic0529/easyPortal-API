<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;


use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\ResellerUpdate
 */
class ResellerUpdateTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $id     = $this->faker->uuid;
        $job    = $this->app->make(ResellerUpdate::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with(
                'data-loader:reseller',
                [
                    'id'       => [$id],
                    '--assets' => true,
                ],
            )
            ->once();

        $job->initialize($id)->handle($kernel);
    }
}
