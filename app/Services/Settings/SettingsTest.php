<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use Mockery;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Settings
 */
class SettingsTest extends TestCase {
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
            $this->app->make(Repository::class),
            Mockery::mock(Storage::class),
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
     * @covers ::getSavedSettings
     */
    public function testGetSavedSettings(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            $this->app->make(Storage::class),
        ) extends Settings {
            /**
             * @inheritDoc
             */
            public function getSavedSettings(): array {
                return parent::getSavedSettings();
            }

            public function getStorage(): Storage {
                return $this->storage;
            }
        };

        // Json
        $expected = [
            'TEST' => 123,
        ];

        $this->assertTrue($service->getStorage()->save($expected));
        $this->assertEquals($expected, $service->getSavedSettings());
    }

    /**
     * @covers ::setEditableSettings
     */
    public function testSetEditableSettings(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            $this->app->make(Storage::class),
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

            protected function isOverridden(string $name): bool {
                return $name === 'READONLY';
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
        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            $this->app->make(Storage::class),
        ) extends Settings {
            /**
             * @inheritDoc
             */
            public function saveSettings(array $settings): bool {
                return parent::saveSettings($settings);
            }

            public function getStorage(): Storage {
                return $this->storage;
            }

            protected function isOverridden(string $name): bool {
                return $name === 'READONLY';
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

        $this->assertTrue($service->getStorage()->save([
            'TEST' => 123,
        ]));
        $this->assertTrue($service->saveSettings($settings));
        $this->assertEquals(['A' => null], $service->getStorage()->load());
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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderParseValue(): array {
        return [
            'null'        => [null, StringType::class, false, null],
            '"null"'      => [null, StringType::class, false, 'null'],
            '(null)'      => [null, StringType::class, false, '(null)'],
            '"null,null"' => ['null,null', StringType::class, false, 'null,null'],
            'null,null'   => [[null, null], IntType::class, true, 'null,null'],
            '1,2,3'       => [[1, 2, 3], IntType::class, true, '1,2,3'],
            '123'         => [123, IntType::class, false, '123'],
            '123 (array)' => [[123], IntType::class, true, '123'],
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
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingsTest_Job extends Job {
    // empty
}
