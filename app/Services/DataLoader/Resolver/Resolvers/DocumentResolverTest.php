<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Document;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\DocumentResolver
 */
class DocumentResolverTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (): Document {
            return Document::factory()->make();
        };

        $a = Document::factory()->create();
        $b = Document::factory()->create();

        self::assertTrue($b->delete());
        self::assertTrue($b->trashed());

        // Run
        $provider = $this->app->make(DocumentResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(1, $queries);

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid();
        $spy     = Mockery::spy(static function () use ($uuid): Document {
            return Document::factory()->make([
                'id'          => $uuid,
                'price'       => null,
                'oem_id'      => $uuid,
                'type_id'     => $uuid,
                'product_id'  => $uuid,
                'customer_id' => $uuid,
                'reseller_id' => $uuid,
                'currency_id' => $uuid,
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals($uuid, $created->getKey());
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($uuid, $factory));
        self::assertCount(0, $queries);
    }
}
