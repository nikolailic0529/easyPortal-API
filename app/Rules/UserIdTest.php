<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\UserId
 */
class UserIdTest extends TestCase {
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
                    'validation.user_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(UserId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory): void {
        $userId = $userFactory($this);
        $this->assertEquals($expected, $this->app->make(UserId::class)->passes('test', $userId));
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
                true,
                static function (): string {
                    $user = User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);

                    return $user->getKey();
                },
            ],
            'not-exists'   => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted' => [
                false,
                static function (): string {
                    $user = User::factory()->create([
                        'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'deleted_at' => Date::now(),
                    ]);
                    return $user->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
