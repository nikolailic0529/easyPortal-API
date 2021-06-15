<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithReseller
 */
class WithResellerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::reseller
     *
     * @dataProvider dataProviderReseller
     */
    public function testResellerExistsThroughProvider(Closure $objectFactory): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($resolver);
        $object  = $objectFactory($this, $reseller);

        $this->assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @covers ::reseller
     *
     * @dataProvider dataProviderReseller
     */
    public function testResellerResellerNotFound(Closure $objectFactory): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $factory = new WithResellerTestObject($resolver);
        $object  = $objectFactory($this, $reseller);

        $this->expectException(ResellerNotFoundException::class);

        $this->assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @covers ::reseller
     */
    public function testResellerAssetWithoutReseller(): void {
        $object   = new ViewAsset();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->never();

        $factory = new WithResellerTestObject($resolver);

        $this->assertNull($factory->reseller($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderReseller(): array {
        return [
            ViewAssetDocument::class => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewAssetDocument([
                        'reseller' => [
                            'id' => $reseller->getKey(),
                        ],
                    ]);
                },
            ],
            ViewDocument::class      => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewDocument([
                        'resellerId' => $reseller->getKey(),
                    ]);
                },
            ],
            ViewAsset::class         => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewAsset([
                        'resellerId' => $reseller->getKey(),
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