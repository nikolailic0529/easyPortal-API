<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomerUpdate
 */
class CustomerUpdateTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $id     = $this->faker->uuid;
        $job    = $this->app->make(CustomerUpdate::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with(
                'ep:data-loader-update-customer',
                [
                    'id'       => [$id],
                    '--assets' => true,
                ],
            )
            ->once();

        $job->initialize($id)->handle($kernel);
    }
}
