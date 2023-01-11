<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Note;
use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\NoteId
 */
class NoteIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.note_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(NoteId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $org    = $this->setOrganization(Organization::factory()->create());
        $rule   = $this->app->make(NoteId::class);
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
                    return Note::factory()
                        ->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'organization_id' => $organization->getKey(),
                        ])
                        ->getKey();
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
                    return Note::factory()
                        ->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'organization_id' => $organization->getKey(),
                            'deleted_at'      => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty value'  => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
