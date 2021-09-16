<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\UserNotMe
 */
class UserNotMeTest extends TestCase {
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
                    'validation.user_not_me' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(UserNotMe::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory, string $value): void {
        $this->setUser($userFactory);
        $this->assertEquals($expected, $this->app->make(UserNotMe::class)->passes('test', $value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'success' => [
                true,
                static function (): User {
                    $user = User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $user;
                },
                'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ],
            'fail'    => [
                false,
                static function (): User {
                    $user = User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $user;
                },
                'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
            ],
        ];
    }
    // </editor-fold>
}
