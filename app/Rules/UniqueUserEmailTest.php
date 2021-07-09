<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\UniqueUserEmail
 */
class UniqueUserEmailTest extends TestCase {
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
                    'validation.unique_user_email' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(UniqueUserEmail::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory): void {
        $email = $userFactory();
        $this->assertEquals($expected, $this->app->make(UniqueUserEmail::class)->passes('test', $email));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'       => [
                false,
                static function (): string {
                    $user = User::factory()->create([
                        'email' => 'test@example.com',
                    ]);
                    return $user->email;
                },
            ],
            'not-exists'   => [
                true,
                static function (): string {
                    return 'test@example.com';
                },
            ],
            'soft-deleted' => [
                true,
                static function (): string {
                    $user = User::factory()->create([
                        'email' => 'test@example.com',
                    ]);
                    $user->delete();
                    return $user->email;
                },
            ],
        ];
    }
    // </editor-fold>
}
