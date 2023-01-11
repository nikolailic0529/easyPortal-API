<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Utils;

use Closure;
use Exception;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Utils\SemanticVersion
 */
class SemanticVersionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderConstruct
     *
     * @param Closure(static, SemanticVersion):SemanticVersion|null $factory
     */
    public function testConstruct(string|Exception $expected, string $version, ?Closure $factory): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $actual = new SemanticVersion($version);

        if ($factory) {
            $actual = $factory($this, $actual);
        }

        self::assertEquals($expected, (string) $actual);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string|Exception, string}>
     */
    public function dataProviderConstruct(): array {
        return [
            'invalid'                          => [
                new InvalidArgumentException('The `invalid` is not a valid Semantic Version string.'),
                'invalid',
                null,
            ],
            'version no patch'                 => [
                new InvalidArgumentException('The `1.2+meta` is not a valid Semantic Version string.'),
                '1.2+meta',
                null,
            ],
            'version'                          => [
                '1.2.3',
                '1.2.3',
                null,
            ],
            'version + pre-release'            => [
                '1.2.3-alpha',
                '1.2.3-alpha',
                null,
            ],
            'version + metadata'               => [
                '1.2.3+meta',
                '1.2.3+meta',
                null,
            ],
            'version + pre-release + metadata' => [
                '1.2.3-beta.123+meta',
                '1.2.3-beta.123+meta',
                null,
            ],
            'update'                           => [
                '1.2.3-beta.123+meta.213',
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version
                        ->setMajorVersion('1')
                        ->setMinorVersion('2')
                        ->setPatchVersion('3')
                        ->setPreRelease('beta.123')
                        ->setMetadata('meta.213');
                },
            ],
            'invalid major version'            => [
                new InvalidArgumentException('The `abc` is not a valid Major version.'),
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version->setMajorVersion('abc');
                },
            ],
            'invalid minor version'            => [
                new InvalidArgumentException('The `abc` is not a valid Minor version.'),
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version->setMinorVersion('abc');
                },
            ],
            'invalid patch version'            => [
                new InvalidArgumentException('The `abc` is not a valid Patch version.'),
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version->setPatchVersion('abc');
                },
            ],
            'invalid pre-release version'      => [
                new InvalidArgumentException('The `abc_123` is not a valid pre-release.'),
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version->setPreRelease('abc_123');
                },
            ],
            'invalid metadata version'         => [
                new InvalidArgumentException('The `abc_123` is not a valid metadata.'),
                '3.2.1',
                static function (self $test, SemanticVersion $version): SemanticVersion {
                    return $version->setMetadata('abc_123');
                },
            ],
        ];
    }
    // </editor-fold>
}
