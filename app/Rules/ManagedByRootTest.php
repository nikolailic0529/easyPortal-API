<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Enums\UserType;
use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\ManagedByRoot
 */
class ManagedByRootTest extends TestCase {
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
                    'validation.manged_by_root' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(ManagedByRoot::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory, Closure $inputFactory): void {
        $this->setUser($userFactory);
        $value = $inputFactory();
        $this->assertEquals($expected, $this->app->make(ManagedByRoot::class)->passes('test', $value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'ok-root by root'       => [
                true,
                static function (): User {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'type' => UserType::local(),
                    ]);
                    return $user;
                },
                static function (): string {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'type' => UserType::local(),
                    ]);
                    return $user->getKey();
                },
            ],
            'ok-not_root by root'   => [
                true,
                static function (): User {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'type' => UserType::local(),
                    ]);
                    return $user;
                },
                static function (): string {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'type' => UserType::keycloak(),
                    ]);
                    return $user->getKey();
                },
            ],
            'fail-root by not_root' => [
                false,
                static function (): User {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'type' => UserType::keycloak(),
                    ]);
                    return $user;
                },
                static function (): string {
                    $user = User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'type' => UserType::local(),
                    ]);
                    return $user->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
