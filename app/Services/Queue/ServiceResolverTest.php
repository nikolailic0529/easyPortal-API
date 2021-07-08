<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Settings\Settings;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\ServiceResolver
 */
class ServiceResolverTest extends TestCase {
    /**
     * @covers ::get
     */
    public function testGet(): void {
        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('getServices')
            ->once()
            ->andReturn([
                ServiceResolverTest_ServiceA::class,
            ]);
        $resolver = new ServiceResolver($this->app, $settings, $this->app->make(Queue::class));

        $this->assertInstanceOf(ServiceResolverTest_ServiceA::class, $resolver->get('service-a'));
    }

    /**
     * @covers ::get
     */
    public function testGetUnknown(): void {
        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('getServices')
            ->once()
            ->andReturn();
        $resolver = new ServiceResolver($this->app, $settings, $this->app->make(Queue::class));

        $this->expectException(ServiceNotFound::class);

        $resolver->get('service-a');
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServiceResolverTest_ServiceA extends CronJob {
    public function displayName(): string {
        return 'service-a';
    }
}
