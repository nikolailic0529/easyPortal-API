<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Organization;
use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\Org\RoleId
 */
class RoleIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $org    = $this->setOrganization(Organization::factory()->create());
        $rule   = $this->app->make(RoleId::class);
        $value  = $valueFactory($this, $org);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
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
            'empty string'  => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    return '';
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
                    return Role::factory()
                        ->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'deleted_at'      => Date::now(),
                            'organization_id' => $organization->getKey(),
                        ])
                        ->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
