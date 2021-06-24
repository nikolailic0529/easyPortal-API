<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Permission;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\PermissionId
 */
class PermissionIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.permissions_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(PermissionId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $permissionFactory): void {
        $permissionId = $permissionFactory();
        $this->assertEquals($expected, $this->app->make(PermissionId::class)->passes('test', $permissionId));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'       => [
                true,
                static function (): string {
                    $permission = Permission::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $permission->getKey();
                },
            ],
            'not-exists'   => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted' => [
                false,
                static function (): string {
                    $permission = Permission::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    $permission->delete();
                    return $permission->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
