<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Currency;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithCurrency
 */
class WithCurrencyTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::currency
     */
    public function testCurrency(): void {
        // Prepare
        $resolver = $this->app->make(CurrencyResolver::class);
        $currency = Currency::factory()->create();
        $factory  = new class($resolver) extends ModelFactory {
            use WithCurrency {
                currency as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CurrencyResolver $currencyResolver,
            ) {
                // empty
            }

            protected function getCurrencyResolver(): CurrencyResolver {
                return $this->currencyResolver;
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };
        $queries  = $this->getQueryLog();

        // If model exists - no action required
        self::assertEquals($currency, $factory->currency($currency->code));
        self::assertCount(1, $queries);

        $queries->flush();

        // If not - it should be created
        $created = $factory->currency('new');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('NEW', $created->code);
        self::assertEquals('new', $created->name);
        self::assertCount(2, $queries);

        $queries->flush();

        // If null or empty - null should be returned
        self::assertNull($factory->currency(null));
        self::assertNull($factory->currency(''));
    }
}
