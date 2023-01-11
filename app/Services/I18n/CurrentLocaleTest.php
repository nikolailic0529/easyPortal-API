<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Translation\Translator;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\I18n\CurrentLocale
 */
class CurrentLocaleTest extends TestCase {
    public function testSet(): void {
        $translator = $this->app->make(Translator::class);
        // Init translations
        $translator->addLines(['test.welcome' => 'Welcome to our application!'], 'en');
        $translator->addLines(['test.welcome' => 'Bienvenue sur notre application!'], 'fr');

        $locale = $this->app->make(CurrentLocale::class);

        $locale->set('en');
        self::assertEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'en'),
        );

        $locale->set('fr');
        self::assertEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'fr'),
        );
        self::assertNotEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'en'),
        );
    }

    /**
     * @dataProvider dataProviderGet
     */
    public function testGet(
        string $expected,
        ?string $userLocale,
        ?string $organizationTimezone,
        ?string $sessionLocale,
    ): void {
        // Organization
        $this->setOrganization(Organization::factory()->create([
            'locale' => $organizationTimezone,
        ]));

        // User
        if ($userLocale) {
            $this->setUser(User::factory()->create([
                'locale' => $userLocale,
            ]));
        }

        // Session
        if ($sessionLocale) {
            $this->app->make(Session::class)->put('locale', $sessionLocale);
        }

        // Default
        $this->app->setLocale('en_BB');

        // Check
        self::assertEquals($expected, $this->app->make(CurrentLocale::class)->get());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGet(): array {
        return [
            'From session'                                         => [
                'fr',
                'en',
                'de',
                'fr',
            ],
            'From user'                                            => [
                'de',
                'de',
                'en',
                null,
            ],
            'From organization'                                    => [
                'de',
                null,
                'de',
                null,
            ],
            'From app config'                                      => [
                'en_BB',
                null,
                null,
                null,
            ],
            'From session without user locale/organization locale' => [
                'fr',
                null,
                null,
                'fr',
            ],
        ];
    }
}
