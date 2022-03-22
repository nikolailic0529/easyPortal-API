<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Organization;
use App\Models\Role;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Org\RoleId
 */
class RoleIdTest extends TestCase {
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
                    'validation.org_role_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(RoleId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $roleFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $rule         = $roleFactory($this, $organization);
        self::assertEquals($expected, $this->app->make(RoleId::class)->passes('test', $rule));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'        => [
                true,
                static function (TestCase $test, Organization $organization): string {
                    $role = Role::factory()->create([
                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'organization_id' => $organization->getKey(),
                    ]);

                    return $role->getKey();
                },
            ],
            'different org' => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $role = Role::factory()->create([
                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'organization_id' => Organization::factory(),
                    ]);

                    return $role->getKey();
                },
            ],
            'not-exists'    => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted'  => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $role = Role::factory()->create([
                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'organization_id' => $organization->getKey(),
                    ]);
                    $role->delete();

                    return $role->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
