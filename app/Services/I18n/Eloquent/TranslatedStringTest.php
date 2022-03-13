<?php declare(strict_types = 1);

namespace App\Services\I18n\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

use function iterator_to_array;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Eloquent\TranslatedString
 */
class TranslatedStringTest extends TestCase {
    /**
     * @covers ::toArray
     * @covers ::offsetGet
     * @covers ::offsetSet
     * @covers ::offsetUnset
     * @covers ::offsetExists
     */
    public function testToArray(): void {
        $translations = new TranslatedString([
            'locale_a' => 'a',
            'locale_b' => 'b',
        ]);

        unset($translations['locale_b']);

        $translations['locale_c'] = 'c';

        $this->assertEquals('a', $translations['locale_a']);
        $this->assertFalse(isset($translations['locale_b']));
        $this->assertEquals(
            [
                'locale_a' => 'a',
                'locale_c' => 'c',
            ],
            $translations->toArray(),
        );
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $this->assertEquals(
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

    /**
     * @covers ::castUsing
     */
    public function testCastUsing(): void {
        $caster = TranslatedString::castUsing([]);
        $model  = new class() extends Model {
            // empty
        };

        // Null
        $this->assertNull($caster->get($model, 'key', null, []));
        $this->assertEquals(['key' => null], $caster->set($model, 'key', null, []));

        // Get
        $value        = json_encode([
            'locale_a' => 'a',
            'locale_b' => 'b',
        ]);
        $translations = $caster->get($model, 'key', $value, []);

        $this->assertInstanceOf(TranslatedString::class, $translations);
        $this->assertEquals(
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

        $this->assertIsArray($translations);
        $this->assertEquals(
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

        $this->assertIsArray($translations);
        $this->assertEquals(
            [
                'key' => null,
            ],
            $translations,
        );
    }
}
