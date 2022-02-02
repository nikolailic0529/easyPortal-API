<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Contact;
use App\Models\Customer;
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
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->name);
        $this->assertEquals('a', $actual->phone_number);
        $this->assertEquals('a', $actual->email);
        $this->assertEquals($ca->getMorphClass(), $actual->object_type);
        $this->assertEquals($ca->getKey(), $actual->object_id);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($ca, ' a ', 'a', 'a', $factory));
        $this->assertSame($actual, $provider->get($ca, 'a', ' a ', 'a', $factory));
        $this->assertSame($actual, $provider->get($ca, ' a ', ' a ', 'a', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($cb, 'name', 'phone', 'email', static function (): Contact {
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

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->name);
        $this->assertEquals('unKnOwn', $created->phone_number);
        $this->assertEquals('unKnOwn', $created->email);
        $this->assertEquals($cb->getMorphClass(), $created->object_type);
        $this->assertEquals($cb->getKey(), $created->object_id);
        $this->assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($cb, ' unknown ', ' unknown ', ' unknown ', $factory));
        $this->assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::get
     */
    public function testGetModelNotExistsWithoutId(): void {
        $model    = new Customer();
        $resolver = Mockery::mock(ContactResolver::class, [$this->app->make(Normalizer::class)]);
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
        $model    = Customer::factory()->make();
        $resolver = Mockery::mock(ContactResolver::class, [$this->app->make(Normalizer::class)]);
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
