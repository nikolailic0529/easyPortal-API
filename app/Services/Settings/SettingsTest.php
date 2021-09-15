<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Environment\EnvironmentRepository;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempFile;
use Mockery;
use ReflectionClassConstant;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Settings
 */
class SettingsTest extends TestCase {
    use WithTempFile;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers \Config\Constants
     */
    public function testConstants(): void {
        $this->assertIsArray($this->app->make(Settings::class)->getEditableSettings());
    }

    /**
     * @covers ::getEditableSettings
     */
    public function testGetEditableSettings(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    #[InternalAttribute]
                    public const B = 'test';

                    #[SettingAttribute('c')]
                    protected const C = 'test';
                })::class;
            }
        };

        $expected = ['A' => 'A'];
        $actual   = array_map(static function (Setting $setting): string {
            return $setting->getName();
        }, $service->getEditableSettings());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getSettings
     */
    public function testGetSettings(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            /**
             * @inheritdoc
             */
            public function getSettings(): array {
                return parent::getSettings();
            }

            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    #[InternalAttribute]
                    public const B = 'test';

                    #[SettingAttribute('c')]
                    protected const C = 'test';
                })::class;
            }
        };

        $expected = ['A' => 'A', 'B' => 'B'];
        $actual   = array_map(static function (Setting $setting): string {
            return $setting->getName();
        }, $service->getSettings());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::setEditableSettings
     */
    public function testSetEditableSettings(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            Mockery::mock(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    public const READONLY = 'readonly';
                })::class;
            }

            /**
             * @inheritDoc
             */
            protected function saveSettings(array $settings): bool {
                return true;
            }

            public function isReadonly(Setting $setting): bool {
                return $setting->getName() === 'READONLY';
            }
        };

        $updated = $service->setEditableSettings([
            [
                // no key and value
            ],
            [
                'name' => 'no value',
            ],
            [
                'value' => 'no name',
            ],
            [
                'name'  => 'UNKNOWN',
                'value' => 'should be ignored',
            ],
            [
                'name'  => 'READONLY',
                'value' => 'should be ignored',
            ],
            [
                'name'  => 'A',
                'value' => 'updated',
            ],
        ]);

        $expected = ['A' => 'updated'];
        $actual   = (new Collection($updated))
            ->keyBy(static function (Setting $setting): string {
                return $setting->getName();
            })
            ->map(static function (Setting $setting): string {
                return $setting->getValue();
            })
            ->all();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::saveSettings
     */
    public function testSaveSettings(): void {
        $path    = $this->getTempFile();
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('getPath')
            ->twice()
            ->andReturn($path);

        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            $storage,
            Mockery::mock(Environment::class),
        ) extends Settings {
            /**
             * @inheritDoc
             */
            public function saveSettings(array $settings): bool {
                return parent::saveSettings($settings);
            }

            public function isReadonly(Setting $setting): bool {
                return $setting->getName() === 'READONLY';
            }

            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    public const READONLY = 'readonly';
                })::class;
            }
        };

        $settings = $service->getEditableSettings();
        $settings = array_map(static function (Setting $setting): Value {
            return new Value($setting, null);
        }, $settings);

        $this->assertTrue($storage->save([
            'TEST' => 123,
        ]));
        $this->assertTrue($service->saveSettings($settings));
        $this->assertEquals(['A' => null], $storage->load());
    }

    /**
     * @covers ::parseValue
     *
     * @dataProvider dataProviderParseValue
     */
    public function testParseValue(mixed $expected, string $type, bool $isArray, mixed $value): void {
        $service = new class() extends Settings {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function parseValue(Setting $setting, ?string $value): mixed {
                return parent::parseValue($setting, $value);
            }
        };

        $setting = Mockery::mock(Setting::class);
        $setting
            ->shouldReceive('getType')
            ->once()
            ->andReturn(new $type());
        $setting
            ->shouldReceive('isArray')
            ->andReturn($isArray);

        $this->assertEquals($expected, $service->parseValue($setting, $value));
    }

    /**
     * @covers ::getServices
     */
    public function testGetServices(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    public const READONLY = 'readonly';

                    #[ServiceAttribute(SettingsTest_Service::class, 'b')]
                    public const SERVICE = 'service';

                    #[JobAttribute(SettingsTest_Job::class, 'b')]
                    public const JOB = 'job';
                })::class;
            }
        };

        $this->assertEquals([SettingsTest_Service::class], $service->getServices());
    }

    /**
     * @covers ::getJobs
     */
    public function testGetJobs(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    public const READONLY = 'readonly';

                    #[ServiceAttribute(SettingsTest_Service::class, 'b')]
                    public const SERVICE = 'service';

                    #[JobAttribute(SettingsTest_Job::class, 'b')]
                    public const JOB = 'job';
                })::class;
            }
        };

        $this->assertEquals([SettingsTest_Job::class], $service->getJobs());
    }

    /**
     * @covers ::getPublicSettings
     */
    public function testGetPublicSettings(): void {
        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            Mockery::mock(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[SettingAttribute('a')]
                    #[PublicName('publicSettingA')]
                    public const A = 'test';

                    #[SettingAttribute('b')]
                    public const READONLY = 'readonly';
                })::class;
            }
        };

        $this->assertEquals(['publicSettingA' => 'null'], $service->getPublicSettings());
    }

    /**
     * @covers ::serializeValue
     *
     * @dataProvider dataProviderSerializeValue
     */
    public function testSerializeValue(
        mixed $expected,
        string $type,
        bool $isArray,
        bool $isSecret,
        mixed $value,
    ): void {
        $service = new class() extends Settings {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function serializeValue(Setting $setting, mixed $value): string {
                return parent::serializeValue($setting, $value);
            }
        };

        $setting = Mockery::mock(Setting::class);
        $setting
            ->shouldReceive('getType')
            ->once()
            ->andReturn(new $type());
        $setting
            ->shouldReceive('isArray')
            ->andReturn($isArray);
        $setting
            ->shouldReceive('isSecret')
            ->andReturn($isSecret);

        $this->assertEquals($expected, $service->serializeValue($setting, $value));
    }

    /**
     * @covers ::serializePublicValue
     *
     * @dataProvider dataProviderSerializePublicValue
     */
    public function testSerializePublicValue(
        mixed $expected,
        string $type,
        bool $isArray,
        bool $isSecret,
        mixed $value,
    ): void {
        $service = new class() extends Settings {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function serializePublicValue(Setting $setting, mixed $value): string {
                return parent::serializePublicValue($setting, $value);
            }
        };

        $setting = Mockery::mock(Setting::class);
        $setting
            ->shouldReceive('getType')
            ->once()
            ->andReturn(new $type());
        $setting
            ->shouldReceive('isArray')
            ->andReturn($isArray);
        $setting
            ->shouldReceive('isSecret')
            ->andReturn($isSecret);

        $this->assertEquals($expected, $service->serializePublicValue($setting, $value));
    }

    /**
     * @covers ::isReadonly
     */
    public function testIsReadonly(): void {
        $env = Mockery::mock(Environment::class);
        $env->shouldAllowMockingProtectedMethods();
        $env->makePartial();
        $env
            ->shouldReceive('getRepository')
            ->twice()
            ->andReturn(new EnvironmentRepository(['A' => 'value']));

        $service  = new Settings(
            Mockery::mock(Application::class),
            Mockery::mock(Repository::class),
            Mockery::mock(Storage::class),
            $env,
        );
        $settingA = Mockery::mock(Setting::class);
        $settingA
            ->shouldReceive('getName')
            ->once()
            ->andReturn('A');
        $settingB = Mockery::mock(Setting::class);
        $settingB
            ->shouldReceive('getName')
            ->once()
            ->andReturn('B');

        $this->assertTrue($service->isReadonly($settingA));
        $this->assertFalse($service->isReadonly($settingB));
    }

    /**
     * @covers ::getPublicValue
     */
    public function testGetPublicValue(): void {
        $A        = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                #[Type(StringType::class)]
                public const A = 123;
            },
            'A',
        ));
        $B        = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute('test.setting')]
                #[Type(StringType::class)]
                public const B = 123;
            },
            'B',
        ));
        $C        = new Value($B, 'abc');
        $settings = $this->app->make(Settings::class);

        $this->setSettings([
            'test.setting' => 345,
        ]);

        $this->assertEquals('123', $settings->getPublicValue($A));
        $this->assertEquals('345', $settings->getPublicValue($B));
        $this->assertEquals('abc', $settings->getPublicValue($C));
    }

    /**
     * @covers ::getPublicDefaultValue
     */
    public function testGetPublicDefaultValue(): void {
        $setting  = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                #[Type(StringType::class)]
                public const TEST = 123;
            },
            'TEST',
        ));
        $settings = $this->app->make(Settings::class);

        $this->assertEquals('123', $settings->getPublicDefaultValue($setting));
    }

    /**
     * @covers ::getValue
     */
    public function testGetValue(): void {
        $app     = Mockery::mock(Application::class);
        $config  = Mockery::mock(Repository::class);
        $storage = Mockery::mock(Storage::class);
        $storage->makePartial();
        $storage
            ->shouldReceive('load')
            ->twice()
            ->andReturn([
                'C' => 321,
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

        $settingA = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute('a.path')]
                public const A = 'A';
            },
            'A',
        ));
        $settingB = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                public const B = 'B';
            },
            'B',
        ));
        $settingC = new Setting(new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                public const C = 'C';
            },
            'C',
        ));
        $settings = Mockery::mock(Settings::class, [$app, $config, $storage, $environment]);
        $settings->shouldAllowMockingProtectedMethods();
        $settings->makePartial();
        $settings
            ->shouldReceive('isEditable')
            ->with($settingA)
            ->once()
            ->andReturn(false);

        $this->assertEquals('A', $settings->getValue($settingA));
        $this->assertEquals('123', $settings->getValue($settingB));
        $this->assertEquals(321, $settings->getValue($settingC));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderParseValue(): array {
        return [
            'null'                 => [null, StringType::class, false, null],
            '"null"'               => [null, StringType::class, false, 'null'],
            '(null)'               => [null, StringType::class, false, '(null)'],
            '"null,null"'          => ['null,null', StringType::class, false, 'null,null'],
            'null,null'            => [[null, null], IntType::class, true, 'null,null'],
            '1,2,3'                => [[1, 2, 3], IntType::class, true, '1,2,3'],
            '123'                  => [123, IntType::class, false, '123'],
            '123 (array)'          => [[123], IntType::class, true, '123'],
            'empty string (array)' => [[], StringType::class, true, ''],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderSerializeValue(): array {
        return [
            'null'                 => ['null', StringType::class, false, false, null],
            '"null"'               => ['null', StringType::class, false, false, 'null'],
            '"null,null"'          => ['null,null', StringType::class, false, false, 'null,null'],
            'null,null'            => ['null,null', IntType::class, true, false, [null, null]],
            '1,2,3'                => ['1,2,3', IntType::class, true, false, [1, 2, 3]],
            '123'                  => ['123', IntType::class, false, false, 123],
            '123 (array)'          => ['123', IntType::class, true, false, [123]],
            '123 (secret)'         => ['123', IntType::class, false, true, 123],
            '123 (secret + array)' => ['123,456', IntType::class, true, true, [123, 456]],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderSerializePublicValue(): array {
        return [
            'null'                 => ['null', StringType::class, false, false, null],
            '"null"'               => ['null', StringType::class, false, false, 'null'],
            '"null,null"'          => ['null,null', StringType::class, false, false, 'null,null'],
            'null,null'            => ['null,null', IntType::class, true, false, [null, null]],
            '1,2,3'                => ['1,2,3', IntType::class, true, false, [1, 2, 3]],
            '123'                  => ['123', IntType::class, false, false, 123],
            '123 (array)'          => ['123', IntType::class, true, false, [123]],
            '123 (secret)'         => ['********', IntType::class, false, true, 123],
            '123 (secret + array)' => ['********,********', IntType::class, true, true, [123, 456]],
        ];
    }
    // </editor-fold>
}


// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingsTest_Service extends CronJob {
    public function displayName(): string {
        return 'service';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingsTest_Job extends Job {
    public function displayName(): string {
        return 'job';
    }
}
