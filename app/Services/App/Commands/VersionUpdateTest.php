<?php declare(strict_types = 1);

namespace App\Services\App\Commands;

use App\Services\App\Events\VersionUpdated;
use App\Services\App\Service;
use App\Services\App\Utils\Composer;
use App\Utils\Console\CommandOptions;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;

use function sprintf;
use function str_starts_with;

/**
 * @internal
 * @coversDefaultClass \App\Services\App\Commands\VersionUpdate
 */
class VersionUpdateTest extends TestCase {
    use CommandOptions;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,string> $arguments
     */
    public function testInvoke(string|Exception $expected, ?string $current, array $arguments): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        } else {
            $this->override(Composer::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('setVersion')
                    ->once()
                    ->andReturn(Command::SUCCESS);
            });

            $this->override(Service::class, static function (MockInterface $mock) use ($current): void {
                $mock
                    ->shouldReceive('getVersion')
                    ->once()
                    ->andReturn($current);
            });
        }

        Event::fake(VersionUpdated::class);

        $this
            ->artisan('ep:app-version-update', $this->getOptions($arguments))
            ->expectsOutput(sprintf('Updating version to `%s`...', $expected))
            ->expectsOutput('Done.')
            ->assertSuccessful();

        Event::assertDispatched(VersionUpdated::class, 1);
        Event::assertDispatched(
            VersionUpdated::class,
            static function (VersionUpdated $event) use ($expected, $current): bool {
                if ($current !== null && str_starts_with($current, 'dev-')) {
                    $current = '0.0.0';
                }

                return $event->getVersion() === $expected
                    && $event->getPrevious() === $current;
            },
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array<string|\Exception,?string,array<string,string>>>
     */
    public function dataProviderInvoke(): array {
        return [
            'invalid version'                                        => [
                new Exception('The `invalid` is not a valid Semantic Version string.'),
                null,
                [
                    'version' => 'invalid',
                ],
            ],
            'no version and no build but current version is known'   => [
                '1.1.1',
                '1.1.1',
                [
                    'version' => '',
                ],
            ],
            'no version and no build but current version is unknown' => [
                '0.0.0',
                null,
                [
                    'version' => '',
                ],
            ],
            'build only'                                             => [
                '1.1.1+123',
                '1.1.1',
                [
                    'version' => '',
                    '--build' => '123',
                ],
            ],
            'valid version and build'                                => [
                '1.2.3+123',
                '1.1.1',
                [
                    'version' => '1.2.3',
                    '--build' => '123',
                ],
            ],
            'valid version, commit and build'                        => [
                '1.2.3+21f1813.123',
                '1.1.1',
                [
                    'version'  => '1.2.3',
                    '--build'  => '123',
                    '--commit' => '21f1813ebe182ff414c9ecc110ea7a148b0e938a',
                ],
            ],
            'valid version and commit'                               => [
                '1.2.3+21f1813',
                '1.1.1',
                [
                    'version'  => '1.2.3',
                    '--commit' => '21f1813ebe182ff414c9ecc110ea7a148b0e938a',
                ],
            ],
            'empty build and commit'                                 => [
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
