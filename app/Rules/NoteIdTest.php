<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Note;
use App\Models\Organization;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\NoteId
 */
class NoteIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.note_id' => 'Translated',
                ],
            ];
        });
        $this->assertEquals($this->app->make(NoteId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $noteFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $note         = $noteFactory($this, $organization);
        $this->assertEquals($expected, $this->app->make(NoteId::class)->passes('test', $note));
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
                static function (TestCase $test, Organization $organization): string {
                    $note = Note::factory()->create([
                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'organization_id' => $organization->getKey(),
                    ]);
                    return $note->getKey();
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
                static function (TestCase $test, Organization $organization): string {
                    $note = Note::factory()->create([
                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'organization_id' => $organization->getKey(),
                    ]);
                    $note->delete();
                    return $note->id;
                },
            ],
        ];
    }
    // </editor-fold>
}
