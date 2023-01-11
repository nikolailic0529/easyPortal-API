<?php declare(strict_types = 1);

namespace App\Services\Queue\Utils;

use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Queue\Queue;
use App\Services\Settings\Settings;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Queue\Utils\ServiceResolver
 */
class ServiceResolverTest extends TestCase {
    public function testGet(): void {
        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('getServices')
            ->once()
            ->andReturn([
                ServiceResolverTest_ServiceA::class,
            ]);
        $resolver = new ServiceResolver($this->app, $settings, $this->app->make(Queue::class));

        self::assertInstanceOf(ServiceResolverTest_ServiceA::class, $resolver->get('service-a'));
    }

    public function testGetUnknown(): void {
        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('getServices')
            ->once()
            ->andReturn();
        $resolver = new ServiceResolver($this->app, $settings, $this->app->make(Queue::class));

        self::expectException(ServiceNotFound::class);

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
