<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\CompositeRule
 */
class CompositeRuleTest extends TestCase {
    public function testPasses(): void {
        $rule = new class($this->app->make(Factory::class)) extends CompositeRule {
            /**
             * @inheritDoc
             */
            protected function getRules(): array {
                return [new Boolean()];
            }
        };

        self::assertTrue($rule->passes('test', true));
        self::assertFalse($rule->passes('test', 123));
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.boolean' => 'message validation.boolean',
                ],
            ];
        });

        $rule = new class($this->app->make(Factory::class)) extends CompositeRule {
            /**
             * @inheritDoc
             */
            protected function getRules(): array {
                return [new Boolean()];
            }
        };

        // Message is empty ...
        self::assertEquals('', $rule->message());

        // ... until fail
        self::assertFalse($rule->passes('test', 123));
        self::assertEquals('message validation.boolean', $rule->message());
    }
}
