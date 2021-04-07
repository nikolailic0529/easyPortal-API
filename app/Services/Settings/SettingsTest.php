<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Filesystem;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Settings
 */
class SettingsTest extends TestCase {
    /**
     * @covers ::getEditableSettings
     */
    public function testGetEditableSettings(): void {
        $store   = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            #[InternalAttribute]
            public const B = 'test';

            #[SettingAttribute('c')]
            protected const C = 'test';
        };
        $service = Mockery::mock(Settings::class, [
            $this->app,
            $this->app->make(Repository::class),
            Mockery::mock(Filesystem::class),
            Mockery::mock(LoggerInterface::class),
        ]);
        $service->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        $service
            ->shouldReceive('getStore')
            ->once()
            ->andReturn($store::class);

        $expected = ['A'];
        $actual   = array_map(static function (Setting $setting): string {
            return $setting->getName();
        }, $service->getEditableSettings());

        $this->assertEquals($expected, $actual);
    }
}
