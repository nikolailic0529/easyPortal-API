<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithReseller
 */
class WithResellerTest extends TestCase {
    /**
     * @covers ::reseller
     */
    public function testResellerExistsThroughProvider(): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($resolver);

        $this->assertEquals($reseller, $factory->reseller(new Asset([
            'id'         => $this->faker->uuid,
            'resellerId' => $reseller->getKey(),
        ])));
    }

    /**
     * @covers ::reseller
     */
    public function testResellerAssetWithoutReseller(): void {
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->never();

        $factory = new WithResellerTestObject($resolver);

        $this->assertNull($factory->reseller(new Asset([
            'id' => $this->faker->uuid,
        ])));
    }

    /**
     * @covers ::reseller
     */
    public function testResellerResellerNotFound(): void {
        $reseller = Reseller::factory()->make();
        $asset    = new Asset([
            'id'         => $this->faker->uuid,
            'resellerId' => $reseller->getKey(),
        ]);
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $factory = new WithResellerTestObject($resolver);

        $this->expectException(ResellerNotFoundException::class);

        $this->assertEquals($reseller, $factory->reseller($asset));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class WithResellerTestObject {
    use WithReseller {
        reseller as public;
    }

    public function __construct(
        protected ResellerResolver $resolver,
    ) {
        // empty
    }

    protected function getResellerResolver(): ResellerResolver {
        return $this->resolver;
    }
}
