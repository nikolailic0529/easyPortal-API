<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\ServiceGroup;
use App\Services\DataLoader\Exceptions\ServiceGroupNotFound;
use App\Services\DataLoader\Factory;
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
        $group    = ServiceGroup::factory()->make();
        $resolver = Mockery::mock(ServiceGroupResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($group->oem, $group->sku)
            ->once()
            ->andReturn($group);

        $factory = new class($resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }
        };

        $this->assertEquals($group, $factory->serviceGroup($group->oem, $group->sku));
    }

    /**
     * @covers ::serviceGroup
     */
    public function testServiceGroupServiceGroupNotFound(): void {
        $group    = ServiceGroup::factory()->make();
        $resolver = Mockery::mock(ServiceGroupResolver::class);
        $resolver
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }
        };

        $this->expectException(ServiceGroupNotFound::class);

        $this->assertEquals($group, $factory->serviceGroup($group->oem, $group->sku));
    }
}
