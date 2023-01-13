<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Tests\TestCase;

use function config;

/**
 * @internal
 * @covers \App\Services\I18n\CurrentTimezone
 */
class CurrentTimezoneTest extends TestCase {
    public function testSet(): void {
        $timezone = $this->app->make(CurrentTimezone::class);
        $expected = 'Test/A';
        $default  = 'Test/F';

        $this->setSettings([
            'app.timezone' => $default,
        ]);

        $timezone->set($expected);

        self::assertEquals($expected, $timezone->get());
        self::assertEquals($default, config('app.timezone'));
    }

    /**
     * @dataProvider dataProviderGet
     */
    public function testGet(
        string $expected,
        ?string $userTimezone,
        ?string $organizationTimezone,
        ?string $sessionTimezone,
    ): void {
        // Organization
        $this->setOrganization(Organization::factory()->create([
            'timezone' => $organizationTimezone,
        ]));

        // User
        if ($userTimezone) {
            $this->setUser(User::factory()->create([
                'timezone' => $userTimezone,
            ]));
        }

        // Session
        if ($sessionTimezone) {
            $this->app->make(Session::class)->put('timezone', $sessionTimezone);
        }

        // Default
        $this->setSettings([
            'app.timezone' => 'Test/F',
        ]);

        // Check
        self::assertEquals($expected, $this->app->make(CurrentTimezone::class)->get());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGet(): array {
        return [
            'From session'                                             => [
                'Test/B',
                'Test/A',
                'Test/D',
                'Test/B',
            ],
            'From user'                                                => [
                'Test/D',
                'Test/D',
                'Test/A',
                null,
            ],
            'From organization'                                        => [
                'Test/D',
                null,
                'Test/D',
                null,
            ],
            'From app config'                                          => [
                'Test/F',
                null,
                null,
                null,
            ],
            'From session without user timezone/organization timezone' => [
                'Test/B',
                null,
                null,
                'Test/B',
            ],
        ];
    }
}
