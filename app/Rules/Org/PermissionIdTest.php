<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Organization;
use App\Models\Permission;
use App\Services\Auth\Auth;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\WithOrganization;

/**
 * @internal
 * @covers \App\Rules\Org\PermissionId
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class PermissionIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.org_permission_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(PermissionId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param OrganizationFactory                     $orgFactory
     * @param array<string>                           $permissions
     * @param Closure(static, ?Organization): ?string $valueFactory
     */
    public function testPasses(
        bool $expected,
        mixed $orgFactory,
        array $permissions,
        Closure $valueFactory,
    ): void {
        $org   = $this->setOrganization($orgFactory);
        $value = $valueFactory($this, $org);

        if ($org && $value) {
            $this->override(Auth::class, static function (MockInterface $mock) use ($org, $permissions): void {
                $mock
                    ->shouldReceive('getAvailablePermissions')
                    ->with($org)
                    ->twice()
                    ->andReturn($permissions);
            });
        }

        $rule   = $this->app->make(PermissionId::class);
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
        $a = 'permission-a';
        $b = 'permission-b';

        return [
            'no organization' => [
                false,
                null,
                [$a, $b],
                static function () use ($b): string {
                    return Permission::factory()
                        ->create([
                            'key' => $b,
                        ])
                        ->getKey();
                },
            ],
            'known'           => [
                true,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function () use ($a): string {
                    return Permission::factory()
                        ->create([
                            'key' => $a,
                        ])
                        ->getKey();
                },
            ],
            'empty string'    => [
                false,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function (): string {
                    return '';
                },
            ],
            'unknown'         => [
                false,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function (): string {
                    return Permission::factory()->make()->getKey();
                },
            ],
            'deleted'         => [
                false,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function () use ($b): string {
                    return Permission::factory()
                        ->create([
                            'key'        => $b,
                            'deleted_at' => Date::now(),
                        ])
                        ->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
