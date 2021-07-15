<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\File;
use App\Models\Note;
use App\Models\Organization;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\FileId
 */
class FileIdTest extends TestCase {
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
                    'validation.file_id' => 'Translated',
                ],
            ];
        });
        $this->assertEquals($this->app->make(FileId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $fileFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $file         = $fileFactory($this, $organization);
        $this->assertEquals($expected, $this->app->make(FileId::class)->passes('test', $file));
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
                    Note::factory()
                        ->hasFiles(1, [
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ])
                        ->create([
                            'organization_id' => $organization->getKey(),
                        ]);
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
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
                        'organization_id' => $organization->getKey(),
                    ]);
                    $file = File::factory()->create([
                        'id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'note_id' => $note->getKey(),
                    ]);
                    $file->delete();
                    return $file->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
