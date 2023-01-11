<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Elastic\ClientBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\Eloquent\SearchableImpl as SearchSearchable;
use Elastic\Client\ClientBuilderInterface;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Searchable as ScoutSearchable;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scout\FieldResolver;
use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function class_uses_recursive;
use function implode;
use function in_array;
use function sprintf;

use const PHP_EOL;

/**
 * @internal
 * @covers \App\Services\Search\Provider
 */
class ProviderTest extends TestCase {
    public function testRegisterBindings(): void {
        self::assertInstanceOf(SearchBuilder::class, $this->app->make(ScoutBuilder::class, [
            'query' => '',
            'model' => new class() extends Model {
                // empty
            },
        ]));

        self::assertInstanceOf(
            SearchRequestFactory::class,
            $this->app->make(SearchParametersFactoryInterface::class),
        );
        self::assertInstanceOf(
            ClientBuilder::class,
            $this->app->make(ClientBuilderInterface::class),
        );

        self::assertTrue($this->app->bound(FieldResolver::class));
    }

    public function testProperSearchableTraitUsed(): void {
        $invalid = Models::get()
            ->filter(static function (ReflectionClass $model): bool {
                $uses   = class_uses_recursive($model->getName());
                $scout  = in_array(ScoutSearchable::class, $uses, true);
                $search = in_array(SearchSearchable::class, $uses, true);

                return $scout && !$search;
            })
            ->keys()
            ->all();
        $message = sprintf(
            'Following models uses `%s` instead of `%s`:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $invalid).PHP_EOL,
            ScoutSearchable::class,
            SearchSearchable::class,
        );

        self::assertEmpty($invalid, $message);
    }
}
