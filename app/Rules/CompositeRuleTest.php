<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\CompositeRule
 */
class CompositeRuleTest extends TestCase {
    /**
     * @covers ::passes
     */
    public function testPasses(): void {
        $rule = new class() extends CompositeRule {
            /**
             * @inheritDoc
             */
            protected function getRules(): array {
                return [new Boolean()];
            }
        };

        $this->assertTrue($rule->passes('test', true));
        $this->assertFalse($rule->passes('test', 123));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.boolean' => 'message validation.boolean',
                ],
            ];
        });

        $rule = new class() extends CompositeRule {
            /**
             * @inheritDoc
             */
            protected function getRules(): array {
                return [new Boolean()];
            }
        };

        // Message is empty ...
        $this->assertEquals('', $rule->message());

        // ... until fail
        $this->assertFalse($rule->passes('test', 123));
        $this->assertEquals('message validation.boolean', $rule->message());
    }
}
