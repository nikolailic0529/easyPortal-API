<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Document;
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
        $normalizer  = $this->app->make(Normalizer::class);
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($distributor->getKey(), Mockery::any())
            ->once()
            ->andReturn($distributor);

        $factory = new WithDistributorTestObject($normalizer, $resolver);
        $object  = $objectFactory($this, $distributor);

        $this->assertEquals($distributor, $factory->distributor($object));
    }

    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorExistsThroughFinder(Closure $objectFactory): void {
        $normalizer  = $this->app->make(Normalizer::class);
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class, [$this->app->make(Normalizer::class)]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->with($distributor->getKey())
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(DistributorFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($distributor->getKey())
            ->once()
            ->andReturn($distributor);

        $factory = new WithDistributorTestObject($normalizer, $resolver, $finder);
        $object  = $objectFactory($this, $distributor);

        $this->assertEquals($distributor, $factory->distributor($object));
    }

    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorDistributorNotFound(Closure $objectFactory): void {
        $normalizer  = $this->app->make(Normalizer::class);
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($distributor->getKey(), Mockery::any())
            ->once()
            ->andReturn(null);

        $factory = new WithDistributorTestObject($normalizer, $resolver);
        $object  = $objectFactory($this, $distributor);

        $this->expectException(DistributorNotFound::class);

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
            Document::class     => [
                static function (TestCase $test, Distributor $distributor) {
                    return new Document([
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
class WithDistributorTestObject extends Factory {
    use WithDistributor {
        distributor as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected Normalizer $normalizer,
        protected DistributorResolver $distributorResolver,
        protected ?DistributorFinder $distributorFinder = null,
    ) {
        // empty
    }

    protected function getDistributorResolver(): DistributorResolver {
        return $this->distributorResolver;
    }

    protected function getDistributorFinder(): ?DistributorFinder {
        return $this->distributorFinder;
    }
}
