<?php declare(strict_types = 1);

namespace App\Services\Settings\Environment;

use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Setting;
use App\Services\Settings\Storage;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Settings\Environment\Configuration
 */
class ConfigurationTest extends TestCase {
    public function testGetConfiguration(): void {
        $app        = Mockery::mock(Application::class);
        $config     = Mockery::mock(Repository::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $storage = Mockery::mock(Storage::class);
        $storage->makePartial();
        $storage
            ->shouldReceive('load')
            ->twice()
            ->andReturn([
                'A' => 321,
            ]);

        $environment = Mockery::mock(Environment::class);
        $environment->shouldAllowMockingProtectedMethods();
        $environment->makePartial();
        $environment
            ->shouldReceive('getRepository')
            ->atLeast()
            ->once()
            ->andReturn(new EnvironmentRepository([
                'B' => '123',
            ]));

        $settingA      = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute('a.path')]
                public const A = 'A';
            },
            'A',
        ));
        $settingB      = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                public const B = 'B';
            },
            'B',
        ));
        $configuration = Mockery::mock(Configuration::class, [$app, $config, $dispatcher, $storage, $environment]);
        $configuration->shouldAllowMockingProtectedMethods();
        $configuration->makePartial();
        $configuration
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn([$settingA, $settingB]);

        $actual   = $configuration->getConfiguration();
        $expected = [
            'envs'   => [
                'A' => '321',
            ],
            'config' => [
                'a.path'           => 321,
                'ep.settings.envs' => [
                    'B' => '123',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
