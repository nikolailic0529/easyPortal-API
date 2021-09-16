<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\OrgUserEmailFree
 */
class OrgUserEmailFreeTest extends TestCase {
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
                    'validation.org_user_email_free' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(OrgUserEmailFree::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $orgUserEmail = $userFactory($this, $organization);
        $this->assertEquals($expected, $this->app->make(OrgUserEmailFree::class)->passes('test', $orgUserEmail));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'in org'               => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $user                = User::factory()->create([
                        'email' => 'test@example.com',
                    ]);
                    $user->organizations = [$organization];
                    $user->save();
                    return $user->email;
                },
            ],
            'in different org'     => [
                true,
                static function (TestCase $test, Organization $organization): string {
                    $user                = User::factory()->create([
                        'email' => 'test@example.com',
                    ]);
                    $organization2       = Organization::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $user->organizations = [$organization2];
                    $user->save();
                    return $user->email;
                },
            ],
            'new user'             => [
                true,
                static function (): string {
                    return 'new@example.com';
                },
            ],
            'soft-deleted/ in org' => [
                true,
                static function (TestCase $test, Organization $organization): string {
                    $user                = User::factory()->create([
                        'email' => 'test@example.com',
                    ]);
                    $user->organizations = [$organization];
                    $user->delete();
                    return $user->email;
                },
            ],
        ];
    }
    // </editor-fold>
}
