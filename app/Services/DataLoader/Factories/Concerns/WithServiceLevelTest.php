<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\ServiceLevel;
use App\Services\DataLoader\Exceptions\ServiceLevelNotFound;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithServiceLevel
 */
class WithServiceLevelTest extends TestCase {
    /**
     * @covers ::serviceLevel
     */
    public function testServiceLevelExistsThroughProvider(): void {
        $level    = ServiceLevel::factory()->make();
        $resolver = Mockery::mock(ServiceLevelResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($level->oem, $level->serviceGroup, $level->sku)
            ->once()
            ->andReturn($level);

        $factory = new class($resolver) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }
        };

        $this->assertEquals($level, $factory->serviceLevel($level->oem, $level->serviceGroup, $level->sku));
    }

    /**
     * @covers ::serviceLevel
     */
    public function testServiceLevelServiceLevelNotFound(): void {
        $level    = ServiceLevel::factory()->make();
        $resolver = Mockery::mock(ServiceLevelResolver::class);
        $resolver
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }
        };

        $this->expectException(ServiceLevelNotFound::class);

        $this->assertEquals($level, $factory->serviceLevel($level->oem, $level->serviceGroup, $level->sku));
    }
}
