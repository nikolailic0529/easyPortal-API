<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Services\DataLoader\Exceptions\OemNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithOem
 */
class WithOemTest extends TestCase {
    /**
     * @covers ::oem
     */
    public function testOemExistsThroughProvider(): void {
        $oem        = Oem::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(OemResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($oem->key, Mockery::any())
            ->once()
            ->andReturn($oem);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithOem {
                oem as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected OemResolver $oemResolver,
            ) {
                // empty
            }

            protected function getOemResolver(): OemResolver {
                return $this->oemResolver;
            }

            protected function getOemFinder(): ?OemFinder {
                return null;
            }
        };

        $this->assertEquals($oem, $factory->oem($oem->key));
    }

    /**
     * @covers ::oem
     */
    public function testOemExistsThroughFinder(): void {
        $oem        = Oem::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(OemResolver::class);
        $finder     = Mockery::mock(OemFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($oem->key)
            ->once()
            ->andReturn($oem);

        $factory = new class($normalizer, $resolver, $finder) extends Factory {
            use WithOem {
                oem as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected OemResolver $oemResolver,
                protected OemFinder $oemFinder,
            ) {
                // empty
            }

            protected function getOemResolver(): OemResolver {
                return $this->oemResolver;
            }

            protected function getOemFinder(): ?OemFinder {
                return $this->oemFinder;
            }
        };

        $this->assertEquals($oem, $factory->oem(" {$oem->key} "));
    }

    /**
     * @covers ::oem
     */
    public function testOemOemNotFound(): void {
        $oem        = Oem::factory()->make();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(OemResolver::class);
        $resolver
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = new class($normalizer, $resolver) extends Factory {
            use WithOem {
                oem as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected OemResolver $oemResolver,
            ) {
                // empty
            }

            protected function getOemResolver(): OemResolver {
                return $this->oemResolver;
            }

            protected function getOemFinder(): ?OemFinder {
                return null;
            }
        };

        $this->expectException(OemNotFound::class);

        $this->assertEquals($oem, $factory->oem($oem->key));
    }
}
