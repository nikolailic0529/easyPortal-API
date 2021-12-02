<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\OrgUserId
 */
class OrgUserIdTest extends TestCase {
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
                    'validation.org_user_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(OrgUserId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $orgUserId    = $userFactory($this, $organization);
        $this->assertEquals($expected, $this->app->make(OrgUserId::class)->passes('test', $orgUserId));
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
                    $user = User::factory()
                        ->hasOrganizations([
                            'organization_id' => $organization->getKey(),
                        ])
                        ->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                    return $user->getKey();
                },
            ],
            'different org' => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $org  = Organization::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $user = User::factory()
                        ->hasOrganizations([
                            'organization_id' => $org->getKey(),
                        ])
                        ->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);

                    return $user->getKey();
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
                    $user = User::factory()
                        ->hasOrganizations([
                            'organization_id' => $organization->getKey(),
                        ])
                        ->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);

                    $user->delete();

                    return $user->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
