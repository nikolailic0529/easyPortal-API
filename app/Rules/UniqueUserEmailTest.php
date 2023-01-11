<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\UniqueUserEmail
 */
class UniqueUserEmailTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
        self::assertEquals($this->app->make(UniqueUserEmail::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $rule   = $this->app->make(UniqueUserEmail::class);
        $value  = $valueFactory($this);
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
                        'email'      => 'test@example.com',
                        'deleted_at' => Date::now(),
                    ]);

                    return $user->email;
                },
            ],
            'empty string' => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
