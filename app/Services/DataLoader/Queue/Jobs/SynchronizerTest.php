<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizer as Processor;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Mockery;
use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Jobs\Synchronizer
 */
class SynchronizerTest extends TestCase {
    public function testGetProcessor(): void {
        $date = Date::now();

        Date::setTestNow($date);

        $settings  = [
            'chunk'           => $this->faker->randomNumber(),
            'force'           => $this->faker->boolean(),
            'expire'          => "PT{$this->faker->randomDigit()}H",
            'outdated'        => $this->faker->boolean(),
            'outdated_limit'  => $this->faker->randomNumber(),
            'outdated_expire' => "PT{$this->faker->randomDigit()}H",
        ];
        $processor = Mockery::mock(Processor::class);
        $processor->makePartial();

        $job = Mockery::mock(Synchronizer::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('makeProcessor')
            ->once()
            ->andReturn($processor);

        self::assertEquals(
            array_keys((array) $job->getQueueConfig()['settings']),
            array_keys($settings),
        );

        $this->setQueueableConfig($job, [
            'settings' => $settings,
        ]);

        $config = $this->app->make(QueueableConfigurator::class)->config($job);
        $actual = $job->getProcessor($this->app, $config);

        self::assertSame($processor, $actual);

        self::assertEquals($settings['chunk'], $actual->getChunkSize());
        self::assertEquals($date->sub($settings['expire']), $actual->getFrom());
        self::assertEquals($settings['force'], $actual->isForce());
        self::assertEquals($settings['outdated'], $actual->isWithOutdated());
        self::assertEquals($settings['outdated_limit'], $actual->getOutdatedLimit());
        self::assertEquals($date->sub($settings['outdated_expire']), $actual->getOutdatedExpire());
    }
}
