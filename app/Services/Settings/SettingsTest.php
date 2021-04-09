<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Disc;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;

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
            $this->app->make(Repository::class),
            Mockery::mock(LoggerInterface::class),
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

        $expected = ['A'];
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
            Mockery::mock(LoggerInterface::class),
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

        $expected = ['A', 'B'];
        $actual   = array_map(static function (Setting $setting): string {
            return $setting->getName();
        }, $service->getSettings());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getSavedSettings
     */
    public function testGetSavedSettings(): void {
        $logger = Mockery::mock(LoggerInterface::class);

        $logger->shouldReceive('warning')->once()->andReturns();

        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            $logger,
        ) extends Settings {
            /**
             * @inheritDoc
             */
            public function getSavedSettings(): array {
                return parent::getSavedSettings();
            }

            public function getDisc(): Disc {
                return parent::getDisc();
            }

            public function getFile(): string {
                return parent::getFile();
            }
        };

        // No file
        $fs = $service->getDisc()->fake();

        $this->assertFalse($fs->exists($service->getFile()));
        $this->assertEquals([], $service->getSavedSettings());

        // Corrupted file
        $this->assertTrue($fs->put($service->getFile(), 'not json}}'));
        $this->assertEquals([], $service->getSavedSettings());

        // Json
        $expected = [
            'TEST' => 123,
        ];

        $this->assertTrue($fs->put($service->getFile(), json_encode($expected)));
        $this->assertEquals($expected, $service->getSavedSettings());
    }

    /**
     * @covers ::setEditableSettings
     */
    public function testSetEditableSettings(): void {
        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            Mockery::mock(LoggerInterface::class),
        ) extends Settings {
            public function getDisc(): Disc {
                return parent::getDisc();
            }

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

        $service->getDisc()->fake();

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
            Mockery::mock(LoggerInterface::class),
        ) extends Settings {
            /**
             * @inheritDoc
             */
            public function saveSettings(array $settings): bool {
                return parent::saveSettings($settings);
            }

            public function getDisc(): Disc {
                return parent::getDisc();
            }

            public function getFile(): string {
                return parent::getFile();
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

        $fs       = $service->getDisc()->fake();
        $settings = $service->getEditableSettings();

        $this->assertTrue($fs->put($service->getFile(), json_encode([
            'TEST' => 123,
        ])));
        $this->assertTrue($service->saveSettings($settings));

        $this->assertEquals(
            json_encode(['A' => null], JSON_PRETTY_PRINT),
            $fs->get($service->getFile()),
        );
    }

    /**
     * @covers ::getValue
     *
     * @dataProvider dataProviderGetValue
     */
    public function testGetValue(mixed $expected, string $type, bool $isArray, mixed $value): void {
        $service = new class() extends Settings {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getValue(Setting $setting, ?string $value): mixed {
                return parent::getValue($setting, $value);
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

        $this->assertEquals($expected, $service->getValue($setting, $value));
    }

    /**
     * @covers ::getServices
     */
    public function testGetServices(): void {
        $service = new class(
            $this->app,
            Mockery::mock(Repository::class),
            Mockery::mock(LoggerInterface::class),
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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGetValue(): array {
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
