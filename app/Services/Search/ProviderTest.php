<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;
use Tests\TestCase;

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
}
