<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use Composer\Package\RootPackage;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\ApplicationInfo
 */
class ApplicationInfoTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderGetVersion
     *
     * @param array<mixed> $package
     */
    public function testGetVersion(string $expected, array $package): void {
        $info = Mockery::mock(ApplicationInfo::class);
        $info->shouldAllowMockingProtectedMethods();
        $info->makePartial();
        $info
            ->shouldReceive('getCachedVersionPath')
            ->once()
            ->andReturn('');
        $info
            ->shouldReceive('getPackageInfo')
            ->once()
            ->andReturn($package);

        self::assertEquals($expected, $info->getVersion());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, array{pretty_version?: string}}>
     */
    public function dataProviderGetVersion(): array {
        return [
            'normal'                            => [
                '1.1.0-rc.1',
                [
                    'pretty_version' => '1.1.0-rc.1',
                ],
            ],
            'dev-branch'                        => [
                ApplicationInfo::DEFAULT_VERSION,
                [
                    'pretty_version' => 'dev-branch',
                ],
            ],
            RootPackage::DEFAULT_PRETTY_VERSION => [
                ApplicationInfo::DEFAULT_VERSION,
                [
                    'pretty_version' => RootPackage::DEFAULT_PRETTY_VERSION,
                ],
            ],
        ];
    }
    // </editor-fold>
}
