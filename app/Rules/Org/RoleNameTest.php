<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Organization;
use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\Org\RoleName
 */
class RoleNameTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.org_role_name' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(RoleName::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): ?Context            $contextFactory
     * @param Closure(static, ?Organization, object|null): string $valueFactory
     */
    public function testPasses(bool $expected, Closure $contextFactory, Closure $valueFactory): void {
        $org     = $this->setOrganization(Organization::factory()->create());
        $rule    = $this->app->make(RoleName::class);
        $context = $contextFactory($this, $org);
        $value   = $valueFactory($this, $org, $context?->getRoot());

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
            'empty string'  => [
                false,
                static function (): ?Context {
                    return null;
                },
                static function (): string {
                    return '';
                },
            ],
            'same'          => [
                true,
                static function (TestCase $test, ?Organization $org): Context {
                    return new ResolverContext(null, Role::factory()->ownedBy($org)->create());
                },
                static function (TestCase $test, ?Organization $org, Role $role): string {
                    return $role->name;
                },
            ],
            'not exists'    => [
                true,
                static function (TestCase $test, ?Organization $org): Context {
                    return new ResolverContext(null, Role::factory()->ownedBy($org)->make());
                },
                static function (TestCase $test): string {
                    return $test->faker->word();
                },
            ],
            'exists'        => [
                false,
                static function (TestCase $test, ?Organization $org): Context {
                    return new ResolverContext(null, Role::factory()->ownedBy($org)->make());
                },
                static function (TestCase $test, ?Organization $org, Role $role): string {
                    Role::factory()->ownedBy($org)->create([
                        'name' => $role->name,
                    ]);

                    return $role->name;
                },
            ],
            'different org' => [
                true,
                static function (TestCase $test, ?Organization $org): Context {
                    return new ResolverContext(null, Role::factory()->ownedBy($org)->make());
                },
                static function (TestCase $test, ?Organization $org, Role $role): string {
                    Role::factory()->ownedBy(Organization::factory()->create())->create();

                    return $role->name;
                },
            ],
            'no context'    => [
                false,
                static function (): ?Context {
                    return null;
                },
                static function (TestCase $test): string {
                    return $test->faker->word();
                },
            ],
            'soft-deleted'  => [
                true,
                static function (TestCase $test, ?Organization $org): Context {
                    return new ResolverContext(null, Role::factory()->ownedBy($org)->create([
                        'deleted_at' => Date::now(),
                    ]));
                },
                static function (TestCase $test, ?Organization $org, Role $role): string {
                    return $role->name;
                },
            ],
        ];
    }
    // </editor-fold>
}
