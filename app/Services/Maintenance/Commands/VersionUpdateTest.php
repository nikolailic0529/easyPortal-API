<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\ApplicationInfo;
use App\Services\Maintenance\Events\VersionUpdated;
use App\Utils\Console\CommandOptions;
use Exception;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @covers \App\Services\Maintenance\Commands\VersionUpdate
 */
class VersionUpdateTest extends TestCase {
    use CommandOptions;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:maintenance-version-update');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:maintenance-version-update', $this->app->make(Kernel::class)->all());
    }

    /**
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,string> $arguments
     */
    public function testInvoke(string|Exception $expected, ?string $current, array $arguments): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        } else {
            $this->override(Filesystem::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('put')
                    ->with('path/to/version.php', Mockery::any())
                    ->once()
                    ->andReturn(true);
            });

            $this->override(ApplicationInfo::class, static function (MockInterface $mock) use ($current): void {
                $mock
                    ->shouldReceive('getCachedVersionPath')
                    ->once()
                    ->andReturn('path/to/version.php');
                $mock
                    ->shouldReceive('getVersion')
                    ->once()
                    ->andReturn($current);
            });
        }

        Event::fake(VersionUpdated::class);

        $this
            ->artisan('ep:maintenance-version-update', $this->getOptions($arguments))
            ->expectsOutput(sprintf('Updating Version to `%s`...', $expected))
            ->expectsOutput('Done.')
            ->assertSuccessful();

        Event::assertDispatched(VersionUpdated::class, 1);
        Event::assertDispatched(
            VersionUpdated::class,
            static function (VersionUpdated $event) use ($expected, $current): bool {
                return $event->getVersion() === $expected
                    && $event->getPrevious() === $current;
            },
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string|Exception,?string,array<string,string>}>
     */
    public function dataProviderInvoke(): array {
        return [
            'invalid version'                                      => [
                new Exception('The `invalid` is not a valid Semantic Version string.'),
                null,
                [
                    'version' => 'invalid',
                ],
            ],
            'no version and no build but current version is known' => [
                '1.1.1',
                '1.1.1',
                [
                    'version' => '',
                ],
            ],
            'build only'                                           => [
                '1.1.1+123',
                '1.1.1',
                [
                    'version' => '',
                    '--build' => '123',
                ],
            ],
            'valid version and build'                              => [
                '1.2.3+123',
                '1.1.1',
                [
                    'version' => '1.2.3',
                    '--build' => '123',
                ],
            ],
            'valid version, commit and build'                      => [
                '1.2.3+21f1813.123',
                '1.1.1',
                [
                    'version'  => '1.2.3',
                    '--build'  => '123',
                    '--commit' => '21f1813ebe182ff414c9ecc110ea7a148b0e938a',
                ],
            ],
            'valid version and commit'                             => [
                '1.2.3+21f1813',
                '1.1.1',
                [
                    'version'  => '1.2.3',
                    '--commit' => '21f1813ebe182ff414c9ecc110ea7a148b0e938a',
                ],
            ],
            'empty build and commit'                               => [
                '1.2.3',
                '1.1.1',
                [
                    'version'  => '1.2.3',
                    '--build'  => '',
                    '--commit' => '',
                ],
            ],
        ];
    }
    //</editor-fold>
}
