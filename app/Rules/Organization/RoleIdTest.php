<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Organization\RoleId
 */
class RoleIdTest extends TestCase {
    use WithoutOrganizationScope;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.organization_role_id' => 'Translated',
                ],
            ];
        });
        $this->assertEquals($this->app->make(RoleId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     *
     * @param \Closure(static): \App\GraphQL\Directives\Directives\Mutation\Context\Context $contextFactory
     * @param \Closure(static, \App\Models\Organization|null): \App\Models\Role             $roleFactory
     */
    public function testPasses(bool $expected, Closure $contextFactory, Closure $roleFactory): void {
        $context = $contextFactory($this);
        $role    = $roleFactory($this, $context?->getRoot());
        $rule    = $this->app->make(RoleId::class);

        if ($context) {
            $rule->setMutationContext($context);
        }

        $this->assertEquals($expected, $rule->passes('test', $role?->getKey()));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'no context'                                      => [
                false,
                static function (): ?Context {
                    return null;
                },
                static function (): ?Role {
                    return null;
                },
            ],
            'role exists'                                     => [
                true,
                static function (): ?Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (self $test, ?Organization $organization): ?Role {
                    return Role::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'shared role'                                     => [
                true,
                static function (): ?Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (): ?Role {
                    return Role::factory()->create([
                        'organization_id' => null,
                    ]);
                },
            ],
            'OrganizationUser'                                => [
                true,
                static function (): ?Context {
                    return new ResolverContext(null, OrganizationUser::factory()->create());
                },
                static function (self $test, OrganizationUser $organization): ?Role {
                    return Role::factory()->create([
                        'organization_id' => $organization->organization_id,
                    ]);
                },
            ],
            'role exists but belongs to another organization' => [
                false,
                static function (): ?Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (self $test, ?Organization $organization): ?Role {
                    return Role::factory()->create([
                        'organization_id' => Organization::factory()->create(),
                    ]);
                },
            ],
            'soft-deleted'                                    => [
                false,
                static function (): ?Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (self $test, ?Organization $organization): ?Role {
                    return Role::factory()->create([
                        'organization_id' => $organization,
                        'deleted_at'      => Date::now(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
