<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\DocumentId
 */
class DocumentIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.document_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(DocumentId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $org    = $this->setOrganization(Organization::factory()->create());
        $rule   = $this->app->make(DocumentId::class);
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
            'exists (quote)'    => [
                true,
                static function (TestCase $test, Organization $org): string {
                    return Document::factory()
                        ->ownedBy($org)
                        ->create([
                            'is_hidden'   => false,
                            'is_contract' => false,
                            'is_quote'    => true,
                        ])
                        ->getKey();
                },
            ],
            'exists (contract)' => [
                true,
                static function (TestCase $test, Organization $org): string {
                    return Document::factory()
                        ->ownedBy($org)
                        ->create([
                            'is_hidden'   => false,
                            'is_contract' => true,
                            'is_quote'    => false,
                        ])
                        ->getKey();
                },
            ],
            'hidden'            => [
                false,
                static function (TestCase $test, Organization $org): string {
                    return Document::factory()
                        ->ownedBy($org)
                        ->create([
                            'is_hidden'   => true,
                            'is_contract' => true,
                            'is_quote'    => true,
                        ])
                        ->getKey();
                },
            ],
            'not a document'    => [
                false,
                static function (TestCase $test, Organization $org): string {
                    return Document::factory()->ownedBy($org)->create()->getKey();
                },
            ],
            'not-exists'        => [
                false,
                static function (): string {
                    return Document::factory()->make()->getKey();
                },
            ],
            'soft-deleted'      => [
                false,
                static function (TestCase $test, Organization $org): string {
                    return Document::factory()
                        ->ownedBy($org)
                        ->create([
                            'is_hidden'   => false,
                            'is_contract' => true,
                            'is_quote'    => true,
                            'deleted_at'  => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty string'      => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
