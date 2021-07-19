<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\ServiceGroup;
use App\Services\DataLoader\Exceptions\ServiceGroupNotFound;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithServiceGroup
 */
class WithServiceGroupTest extends TestCase {
    /**
     * @covers ::serviceGroup
     */
    public function testServiceGroupExistsThroughProvider(): void {
        $group      = ServiceGroup::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ServiceGroupResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($group->oem, $group->sku, Mockery::any())
            ->once()
            ->andReturn($group);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }

            protected function getServiceGroupFinder(): ?ServiceGroupFinder {
                return null;
            }
        };

        $this->assertEquals($group, $factory->serviceGroup($group->oem, " {$group->sku} "));
    }

    /**
     * @covers ::serviceGroup
     */
    public function testServiceGroupExistsThroughFinder(): void {
        $group      = ServiceGroup::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ServiceGroupResolver::class);
        $finder     = Mockery::mock(ServiceGroupFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($group->oem, $group->sku)
            ->once()
            ->andReturn($group);

        $factory = new class($normalizer, $resolver, $finder) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceGroupFinder $serviceGroupFinder,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }

            protected function getServiceGroupFinder(): ?ServiceGroupFinder {
                return $this->serviceGroupFinder;
            }
        };

        $this->assertEquals($group, $factory->serviceGroup($group->oem, " {$group->sku} "));
    }

    /**
     * @covers ::serviceGroup
     */
    public function testServiceGroupServiceGroupNotFound(): void {
        $group      = ServiceGroup::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ServiceGroupResolver::class);
        $resolver
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }

            protected function getServiceGroupFinder(): ?ServiceGroupFinder {
                return null;
            }
        };

        $this->expectException(ServiceGroupNotFound::class);

        $this->assertEquals($group, $factory->serviceGroup($group->oem, $group->sku));
    }
}
