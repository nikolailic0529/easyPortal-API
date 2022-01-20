<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Organization;
use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use Closure;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Organization\PermissionId
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
                    'validation.organization_permissions_id' => 'Translated',
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
     *
     * @param array<\App\Services\Auth\Permission> $permissions
     * @param \Closure(): \App\Models\Permission   $permissionFactory
     */
    public function testPasses(
        bool $expected,
        ?Closure $orgFactory,
        array $permissions,
        Closure $permissionFactory,
    ): void {
        $org = $orgFactory ? $orgFactory($this) : null;

        if ($org) {
            $this->override(Auth::class, static function (MockInterface $mock) use ($org, $permissions): void {
                $mock
                    ->shouldReceive('getAvailablePermissions')
                    ->with($org)
                    ->once()
                    ->andReturn($permissions);
            });
        }

        $permission = $permissionFactory($this, $org);
        $rule       = $this->app->make(PermissionId::class)->setMutationContext(new ResolverContext(null, $org));
        $actual     = $rule->passes('test', $permission->getKey());

        $this->assertEquals($expected, $actual);
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
                static function () use ($b): Permission {
                    return Permission::factory()->create([
                        'key' => $b->getName(),
                    ]);
                },
            ],
            'known'           => [
                true,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function () use ($a): Permission {
                    return Permission::factory()->create([
                        'key' => $a->getName(),
                    ]);
                },
            ],
            'unknown'         => [
                false,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function (): Permission {
                    return Permission::factory()->make();
                },
            ],
            'deleted'         => [
                false,
                static function (): Organization {
                    return Organization::factory()->create();
                },
                [$a, $b],
                static function () use ($b): Permission {
                    return Permission::factory()->create([
                        'key'        => $b->getName(),
                        'deleted_at' => Date::now(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
