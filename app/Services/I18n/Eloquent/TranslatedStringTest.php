<?php declare(strict_types = 1);

namespace App\Services\I18n\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

use function iterator_to_array;
use function json_encode;

/**
 * @internal
 * @covers \App\Services\I18n\Eloquent\TranslatedString
 */
class TranslatedStringTest extends TestCase {
    public function testToArray(): void {
        $translations = new TranslatedString([
            'locale_a' => 'a',
            'locale_b' => 'b',
        ]);

        unset($translations['locale_b']);

        $translations['locale_c'] = 'c';

        self::assertEquals('a', $translations['locale_a']);
        self::assertFalse(isset($translations['locale_b']));
        self::assertEquals(
            [
                'locale_a' => 'a',
                'locale_c' => 'c',
            ],
            $translations->toArray(),
        );
    }

    public function testGetIterator(): void {
        self::assertEquals(
            [
                'locale_a' => [
                    'locale' => 'locale_a',
                    'text'   => 'a',
                ],
                'locale_b' => [
                    'locale' => 'locale_b',
                    'text'   => 'b',
                ],
            ],
            iterator_to_array(new TranslatedString([
                'locale_a' => 'a',
                'locale_b' => 'b',
            ])),
        );
    }

    public function testCastUsing(): void {
        $caster = TranslatedString::castUsing([]);
        $model  = new class() extends Model {
            // empty
        };

        // Null
        self::assertNull($caster->get($model, 'key', null, []));
        self::assertEquals(['key' => null], $caster->set($model, 'key', null, []));

        // Get
        $value        = json_encode([
            'locale_a' => 'a',
            'locale_b' => 'b',
        ]);
        $translations = $caster->get($model, 'key', $value, []);

        self::assertInstanceOf(TranslatedString::class, $translations);
        self::assertEquals(
            [
                'locale_a' => 'a',
                'locale_b' => 'b',
            ],
            $translations->toArray(),
        );

        // Set
        $value        = new TranslatedString([
            'locale_b' => 'b',
            'locale_a' => 'a',
        ]);
        $translations = $caster->set($model, 'key', $value, []);

        self::assertIsArray($translations);
        self::assertEquals(
            [
                'key' => json_encode([
                    'locale_a' => 'a',
                    'locale_b' => 'b',
                ]),
            ],
            $translations,
        );

        // Set empty
        $value        = new TranslatedString([]);
        $translations = $caster->set($model, 'key', $value, []);

        self::assertIsArray($translations);
        self::assertEquals(
            [
                'key' => null,
            ],
            $translations,
        );
    }
}
