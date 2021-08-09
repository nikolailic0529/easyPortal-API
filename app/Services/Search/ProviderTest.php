<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\Eloquent\Searchable as SearchSearchable;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Searchable as ScoutSearchable;
use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function array_diff;
use function array_keys;
use function class_uses_recursive;
use function implode;
use function in_array;
use function sprintf;

use const PHP_EOL;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Provider
 */
class ProviderTest extends TestCase {
    /**
     * @covers ::registerBindings
     */
    public function testRegisterBindings(): void {
        $this->assertInstanceOf(SearchBuilder::class, $this->app->make(ScoutBuilder::class, [
            'query' => '',
            'model' => new class() extends Model {
                // empty
            },
        ]));

        $this->assertInstanceOf(SearchRequestFactory::class, $this->app->make(SearchRequestFactoryInterface::class));
    }

    public function testAllSearchableModelRegistered(): void {
        $expected = array_keys(Models::get(static function (ReflectionClass $model): bool {
            return in_array(SearchSearchable::class, class_uses_recursive($model->getName()), true);
        }));
        $actual   = (new class() extends Provider {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @return array<class-string<\App\Services\Logger\Models\Model&\App\Services\Search\Eloquent\Searchable>>
             */
            public function getRegisteredModels(): array {
                return self::$searchable;
            }
        })->getRegisteredModels();
        $missed   = array_diff($expected, $actual);
        $message  = 'Following models missed in $searchable:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $missed).PHP_EOL;

        $this->assertEmpty($missed, $message);
    }

    public function testProperSearchableTraitUsed(): void {
        $invalid = array_keys(Models::get(static function (ReflectionClass $model): bool {
            $uses   = class_uses_recursive($model->getName());
            $scout  = in_array(ScoutSearchable::class, $uses, true);
            $search = in_array(SearchSearchable::class, $uses, true);

            return $scout && !$search;
        }));
        $message = sprintf(
            'Following models uses `%s` instead of `%s`:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $invalid).PHP_EOL,
            ScoutSearchable::class,
            SearchSearchable::class,
        );

        $this->assertEmpty($invalid, $message);
    }
}
