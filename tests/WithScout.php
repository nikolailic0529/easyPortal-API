<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Model;
use Elasticsearch\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

use function str_starts_with;

/**
 * @mixin \Tests\TestCase
 */
trait WithScout {
    protected function setUpWithScout(): void {
        // Right now only ElasticSearch is supported.
        $this->setSettings([
            'scout.driver'                           => 'elastic',
            'scout.prefix'                           => 'testing_',
            'scout.queue'                            => false,
            'elastic.scout_driver.refresh_documents' => true,
        ]);

        // Remove all indexes
        $client  = $this->app->make(Client::class);
        $prefix  = $this->app->make(Repository::class)->get('scout.prefix');
        $indexes = (new Collection($client->cat()->indices()))
            ->map(static function (array $index): string {
                return $index['index'];
            })
            ->filter(static function (string $index) use ($prefix): bool {
                return str_starts_with($index, $prefix);
            })
            ->join(',');

        if ($indexes) {
            $client->indices()->delete(['index' => $indexes]);
        }
    }

    protected function tearDownWithScout(): void {
        // empty
    }

    protected function makeSearchable(Collection|Model $models): Collection|Model {
        if ($models instanceof Model) {
            $models->searchable();
        } else {
            // Foreach is used because Scout doesn't create an index right if the
            // collection contains models of different classes.
            foreach ($models as $model) {
                $this->makeSearchable($model);
            }
        }

        return $models;
    }
}
