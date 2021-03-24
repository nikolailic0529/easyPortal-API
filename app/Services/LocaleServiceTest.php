<?php declare(strict_types = 1);

namespace App\Services;

use App\Models\User;
use App\Services\LocaleService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Translation\Translator;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\LocaleService
 */
class LocaleServiceTest extends TestCase {

    /**
     * @covers ::set
     */
    public function testSet(): void {
        $translator = $this->app->make(Translator::class);
        // Init translations
        $translator->addLines(['test.welcome' => 'Welcome to our application!' ], 'en');
        $translator->addLines(['test.welcome' => 'Bienvenue sur notre application!' ], 'fr');

        $locale = $this->app->make(LocaleService::class);

        $locale->set('en');
        $this->assertEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'en'),
        );

        $locale->set('fr');
        $this->assertEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'fr'),
        );
        $this->assertNotEquals(
            $translator->get('test.welcome'),
            $translator->get('test.welcome', [], 'en'),
        );
    }

    /**
     * @covers ::get
     *
     * @dataProvider dataProviderGet
     */
    public function testGet(string $expected, ?string $userLocale, ?string $sessionLocale): void {
        if ($userLocale) {
            $this->setUser(User::factory()->create([
                'locale' => $userLocale,
            ]));
        }

        if ($sessionLocale) {
            $this->app->make(Session::class)->put('locale', $sessionLocale);
        }

        $this->app->setLocale('en_UK');

        $this->assertEquals($expected, $this->app->make(LocaleService::class)->get());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGet(): array {
        return [
            'From session'                     => [
                'fr',
                'en',
                'fr',
            ],
            'From user'                        => [
                'de',
                'de',
                null,
            ],
            'From app config'                  => [
                'en_UK',
                null,
                null,
            ],
            'From session without user locale' => [
                'fr',
                null,
                'fr',
            ],
        ];
    }
}
