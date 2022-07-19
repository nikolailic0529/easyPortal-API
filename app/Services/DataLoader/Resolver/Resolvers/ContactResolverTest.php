<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Normalizer\Normalizer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\ContactResolver
 */
class ContactResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $ca      = Customer::factory()->create();
        $cb      = Customer::factory()->create();
        $factory = static function (): Contact {
            return Contact::factory()->make();
        };

        Contact::factory()->create([
            'object_type'  => $ca->getMorphClass(),
            'object_id'    => $ca,
            'name'         => 'a',
            'phone_number' => 'a',
            'email'        => 'a',
        ]);
        Contact::factory()->create([
            'object_type'  => $ca->getMorphClass(),
            'object_id'    => $ca,
            'name'         => 'b',
            'phone_number' => 'b',
            'email'        => 'b',
        ]);

        // Run
        $provider = $this->app->make(ContactResolver::class);
        $actual   = $provider->get($ca, 'a', 'a', 'a', $factory);

        $this->flushQueryLog();

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->name);
        self::assertEquals('a', $actual->phone_number);
        self::assertEquals('a', $actual->email);
        self::assertEquals($ca->getMorphClass(), $actual->object_type);
        self::assertEquals($ca->getKey(), $actual->object_id);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($ca, ' a ', 'a', 'a', $factory));
        self::assertSame($actual, $provider->get($ca, 'a', ' a ', 'a', $factory));
        self::assertSame($actual, $provider->get($ca, ' a ', ' a ', 'a', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($cb, 'name', 'phone', 'email', static function (): Contact {
            return Contact::factory()->make();
        }));

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function () use ($cb): Contact {
            return Contact::factory()->create([
                'object_type'  => $cb->getMorphClass(),
                'object_id'    => $cb,
                'name'         => 'unKnown',
                'phone_number' => 'unKnOwn',
                'email'        => 'unKnOwn',
            ]);
        });
        $created = $provider->get($cb, ' unKnown ', ' unKnOwn ', ' unKnOwn ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->name);
        self::assertEquals('unKnOwn', $created->phone_number);
        self::assertEquals('unKnOwn', $created->email);
        self::assertEquals($cb->getMorphClass(), $created->object_type);
        self::assertEquals($cb->getKey(), $created->object_id);
        self::assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($cb, ' unknown ', ' unknown ', ' unknown ', $factory));
        self::assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::get
     */
    public function testGetModelNotExistsWithoutId(): void {
        $model      = new Customer();
        $collector  = $this->app->make(Collector::class);
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ContactResolver::class, [$normalizer, $collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('resolve')
            ->with(Mockery::any(), null, false)
            ->once()
            ->andReturn(null);

        $resolver->get($model, '', '', '');
    }

    /**
     * @covers ::get
     */
    public function testGetModelNotExistsWithId(): void {
        $model      = Customer::factory()->make();
        $collector  = $this->app->make(Collector::class);
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = Mockery::mock(ContactResolver::class, [$normalizer, $collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('resolve')
            ->with(Mockery::any(), null, false)
            ->once()
            ->andReturn(null);

        $resolver->get($model, '', '', '');
    }
}
