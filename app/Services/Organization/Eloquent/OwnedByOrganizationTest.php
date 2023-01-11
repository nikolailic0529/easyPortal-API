<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function class_uses_recursive;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 * @covers \App\Services\Organization\Eloquent\OwnedByOrganization
 */
class OwnedByOrganizationTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testImplements(): void {
        $invalid = Models::get()
            ->filter(static function (ReflectionClass $class): bool {
                return !$class->implementsInterface(OwnedByOrganization::class)
                    && in_array(OwnedByOrganizationImpl::class, class_uses_recursive($class->getName()), true);
            })
            ->map(static function (ReflectionClass $class): string {
                return $class->getName();
            })
            ->all();
        $message = sprintf(
            "Following models must implements `%s`:\n%s",
            OwnedByOrganization::class,
            '- '.implode("\n- ", $invalid),
        );

        self::assertEmpty($invalid, $message);
    }

    /**
     * @coversNothing
     */
    public function testTrait(): void {
        $invalid = Models::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->implementsInterface(OwnedByOrganization::class)
                    && !in_array(OwnedByOrganizationImpl::class, class_uses_recursive($class->getName()), true);
            })
            ->map(static function (ReflectionClass $class): string {
                return $class->getName();
            })
            ->all();
        $message = sprintf(
            "Following models must use `%s`:\n%s",
            OwnedByOrganizationImpl::class,
            '- '.implode("\n- ", $invalid),
        );

        self::assertEmpty($invalid, $message);
    }

    /**
     * @coversNothing
     */
    public function testExclusive(): void {
        $conflicts = [
            OwnedByReseller::class,
        ];
        $invalid   = Models::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->implementsInterface(OwnedByOrganization::class);
            })
            ->filter(static function (ReflectionClass $class) use ($conflicts): bool {
                foreach ($conflicts as $conflict) {
                    if ($class->implementsInterface($conflict)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(static function (ReflectionClass $class): string {
                return $class->getName();
            })
            ->all();
        $message   = sprintf(
            "Following models must implements `%s` only:\n%s",
            OwnedByOrganization::class,
            '- '.implode("\n- ", $invalid),
        );

        self::assertEmpty($invalid, $message);
    }
}
