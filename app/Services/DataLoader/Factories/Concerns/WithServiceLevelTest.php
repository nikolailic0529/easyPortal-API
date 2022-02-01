<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\ServiceLevel;
use App\Services\DataLoader\Exceptions\ServiceLevelNotFound;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
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
        $level      = ServiceLevel::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ServiceLevelResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($level->oem, $level->serviceGroup, $level->sku, Mockery::any())
            ->once()
            ->andReturn($level);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }

            protected function getServiceLevelFinder(): ?ServiceLevelFinder {
                return null;
            }
        };

        $this->assertEquals($level, $factory->serviceLevel($level->oem, $level->serviceGroup, $level->sku));
    }

    /**
     * @covers ::serviceLevel
     */
    public function testServiceLevelExistsThroughFinder(): void {
        $level      = ServiceLevel::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ServiceLevelResolver::class);
        $finder     = Mockery::mock(ServiceLevelFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($level->oem, $level->serviceGroup, $level->sku)
            ->once()
            ->andReturn($level);

        $factory = new class($normalizer, $resolver, $finder) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected ServiceLevelFinder $serviceLevelFinder,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }

            protected function getServiceLevelFinder(): ?ServiceLevelFinder {
                return $this->serviceLevelFinder;
            }
        };

        $this->assertEquals($level, $factory->serviceLevel($level->oem, $level->serviceGroup, " {$level->sku} "));
    }

    /**
     * @covers ::serviceLevel
     */
    public function testServiceLevelServiceLevelNotFound(): void {
        $level      = ServiceLevel::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ServiceLevelResolver::class);
        $resolver
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }

            protected function getServiceLevelFinder(): ?ServiceLevelFinder {
                return null;
            }
        };

        $this->expectException(ServiceLevelNotFound::class);

        $this->assertEquals($level, $factory->serviceLevel($level->oem, $level->serviceGroup, $level->sku));
    }
}
