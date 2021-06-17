<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFoundException;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithDistributor
 */
class WithDistributorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorExistsThroughProvider(Closure $objectFactory): void {
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($distributor->getKey())
            ->once()
            ->andReturn($distributor);

        $factory = new WithDistributorTestObject($resolver);
        $object  = $objectFactory($this, $distributor);

        $this->assertEquals($distributor, $factory->distributor($object));
    }

    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorDistributorNotFound(Closure $objectFactory): void {
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($distributor->getKey())
            ->once()
            ->andReturn(null);

        $factory = new WithDistributorTestObject($resolver);
        $object  = $objectFactory($this, $distributor);

        $this->expectException(DistributorNotFoundException::class);

        $this->assertEquals($distributor, $factory->distributor($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderDistributor(): array {
        return [
            ViewDocument::class => [
                static function (TestCase $test, Distributor $distributor) {
                    return new ViewDocument([
                        'distributorId' => $distributor->getKey(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class WithDistributorTestObject {
    use WithDistributor {
        distributor as public;
    }

    public function __construct(
        protected DistributorResolver $resolver,
    ) {
        // empty
    }

    protected function getDistributorResolver(): DistributorResolver {
        return $this->resolver;
    }
}
