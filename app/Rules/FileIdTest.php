<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\File;
use App\Models\Note;
use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\FileId
 */
class FileIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.file_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(FileId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $org    = $this->setOrganization(Organization::factory()->create());
        $rule   = $this->app->make(FileId::class);
        $value  = $valueFactory($this, $org);
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
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'object_id'   => $note->getKey(),
                        'object_type' => $note->getMorphClass(),
                    ]);
                    $file->delete();

                    return $file->getKey();
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
