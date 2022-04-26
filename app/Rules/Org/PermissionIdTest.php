<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Organization;
use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Org\PermissionId
 *
 * @phpstan-import-type OrganizationFactory from \Tests\WithOrganization
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
                    'validation.org_permission_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(PermissionId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     *
     * @param OrganizationFactory                     $orgFactory
     * @param array<AuthPermission>                   $permissions
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
        $a = new class('permission-a') extends AuthPermission {
            // empty,
        };
        $b = new class('permission-b') extends AuthPermission {
            // empty,
        };

        return [
            'no organization' => [
                false,
                null,
                [$a, $b],
                static function () use ($b): string {
                    return Permission::factory()
                        ->create([
                            'key' => $b->getName(),
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
                            'key' => $a->getName(),
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
                            'key'        => $b->getName(),
                            'deleted_at' => Date::now(),
                        ])
                        ->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
