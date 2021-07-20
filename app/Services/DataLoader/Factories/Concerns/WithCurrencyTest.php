<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Currency;
use App\Models\Model;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Schema\Type;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithCurrency
 */
class WithCurrencyTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::currency
     */
    public function testCurrency(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(CurrencyResolver::class);
        $currency   = Currency::factory()->create();
        $factory    = new class($normalizer, $resolver) extends ModelFactory {
            use WithCurrency {
                currency as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CurrencyResolver $currencyResolver,
            ) {
                // empty
            }

            protected function getNormalizer(): Normalizer {
                return $this->normalizer;
            }

            protected function getCurrencyResolver(): CurrencyResolver {
                return $this->currencyResolver;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($currency, $factory->currency($currency->code));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->currency('new ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('new', $created->code);
        $this->assertEquals('new', $created->name);
        $this->assertCount(2, $this->getQueryLog());

        // If null - null should be returned
        $this->assertNull($factory->currency(null));
    }
}
