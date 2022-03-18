<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function array_diff;
use function class_uses_recursive;
use function count;
use function implode;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Organization\Eloquent\OwnedByOrganizationImpl
 */
class OwnedByOrganizationTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testImplements(): void {
        $invalid = Models::get()
            ->filter(static function (ReflectionClass $class): bool {

                $uses = class_uses_recursive($class->getName());
                $impl = [
                    OwnedByOrganizationImpl::class,
                    OwnedByResellerImpl::class,
                ];
                $diff = array_diff($impl, $uses);

                return !$class->implementsInterface(OwnedByOrganization::class)
                    && count($diff) !== count($impl);
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
}
