<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Rules\Organization\RoleId
 */
class RoleIdTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.organization_role_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(RoleId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): ?Context             $contextFactory
     * @param Closure(static, object|null): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $contextFactory, Closure $valueFactory): void {
        $context = $contextFactory($this);
        $value   = $valueFactory($this, $context?->getRoot());
        $rule    = $this->app->make(RoleId::class);

        if ($context) {
            $rule->setMutationContext($context);
        }

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
            'no context'                                      => [
                false,
                static function (): ?Context {
                    return null;
                },
                static function (): ?string {
                    return null;
                },
            ],
            'role exists'                                     => [
                true,
                static function (): Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (self $test, ?Organization $org): string {
                    return Role::factory()
                        ->create([
                            'organization_id' => $org,
                        ])
                        ->getKey();
                },
            ],
            'shared role'                                     => [
                true,
                static function (): Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (): string {
                    return Role::factory()
                        ->create([
                            'organization_id' => null,
                        ])
                        ->getKey();
                },
            ],
            'OrganizationUser'                                => [
                true,
                static function (): Context {
                    return new ResolverContext(null, OrganizationUser::factory()->create());
                },
                static function (self $test, OrganizationUser $user): string {
                    return Role::factory()
                        ->create([
                            'organization_id' => $user->organization_id,
                        ])
                        ->getKey();
                },
            ],
            'role exists but belongs to another organization' => [
                false,
                static function (): Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (): string {
                    return Role::factory()
                        ->create([
                            'organization_id' => Organization::factory()->create(),
                        ])
                        ->getKey();
                },
            ],
            'soft-deleted'                                    => [
                false,
                static function (): Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (self $test, ?Organization $org): string {
                    return Role::factory()
                        ->create([
                            'organization_id' => $org,
                            'deleted_at'      => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty string'                                    => [
                false,
                static function (): Context {
                    return new ResolverContext(null, Organization::factory()->create());
                },
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
