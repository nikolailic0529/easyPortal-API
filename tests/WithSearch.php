<?php declare(strict_types = 1);

namespace Tests;

use App\Utils\Eloquent\Model;
use Elasticsearch\Client;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\ParallelTesting;
use Throwable;

use function str_starts_with;

/**
 * @mixin \Tests\TestCase
 */
trait WithSearch {
    // <editor-fold desc="SetUp">
    // =========================================================================
    /**
     * @before
     */
    public function initWithSearch(): void {
        $this->afterApplicationCreated(function (): void {
            // Right now only Elasticsearch is supported.
            $token  = ParallelTesting::token();
            $prefix = $token ? "testing_{$token}_" : 'testing_';

            $this->setSettings([
                'scout.driver'                           => 'elastic',
                'scout.prefix'                           => $prefix,
                'scout.queue'                            => false,
                'elastic.scout_driver.refresh_documents' => true,
            ]);

            // Available?
            $client = $this->app->make(Client::class);

            try {
                $client->info();
            } catch (Exception) {
                $this->markTestSkipped('Elastic Search is not installed/configured.');
            }

            // Remove all indexes
            $indexes = (new Collection($client->indices()->getAlias()))
                ->keys()
                ->filter(function (string $index): bool {
                    return $this->isSearchName($index);
                })
                ->join(',');

            if ($indexes) {
                $client->indices()->delete(['index' => $indexes]);
            }
        });
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @template T of \Illuminate\Support\Collection|\App\Utils\Eloquent\Model
     *
     * @param T $models
     *
     * @return T
     */
    protected function makeSearchable(Collection|Model $models): Collection|Model {
        if ($models instanceof Model) {
            /** @var \App\Services\Search\Eloquent\Searchable $models */
            $config   = $models->getSearchConfiguration();
            $index    = $config->getIndexName();
            $alias    = $config->getIndexAlias();
            $mappings = $config->getMappings();

            $this->createSearchIndex($index, $alias, $mappings);
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

    /**
     * @param array<mixed>|null $mappings
     */
    protected function createSearchIndex(string $index, string $alias = null, array $mappings = null): void {
        $client = $this->app->make(Client::class)->indices();
        $index  = $this->getSearchName($index);
        $alias  = $this->getSearchName($alias);

        if (!$client->exists(['index' => $index])) {
            if ($mappings) {
                $client->create([
                    'index' => $index,
                    'body'  => [
                        'mappings' => $mappings,
                    ],
                ]);
            } else {
                $client->create([
                    'index' => $index,
                ]);
            }
        }

        if ($alias) {
            $client->updateAliases([
                'body' => [
                    'actions' => [
                        // Remove alias from old index
                        [
                            'remove' => [
                                'index' => '*',
                                'alias' => $alias,
                            ],
                        ],
                        // Add alias to new index
                        [
                            'add' => [
                                'index'          => $index,
                                'alias'          => $alias,
                                'is_write_index' => true,
                            ],
                        ],
                    ],
                ],
            ]);

            $this->assertSearchIndexAlias($index, $alias);
        } else {
            $this->assertSearchIndexExists($index);
        }
    }

    private function getSearchIndexPrefix(): string {
        return $this->app->make(Repository::class)->get('scout.prefix');
    }

    private function getSearchName(?string $index): ?string {
        if ($index !== null && !$this->isSearchName($index)) {
            $index = "{$this->getSearchIndexPrefix()}{$index}";
        }

        return $index;
    }

    private function isSearchName(string $index): bool {
        return str_starts_with($index, $this->getSearchIndexPrefix());
    }
    // </editor-fold>

    // <editor-fold desc="Assertions">
    // =========================================================================
    /**
     * @param array<mixed> $expected
     */
    protected function assertSearchIndexes(array $expected, string $message = ''): void {
        // Prepare Expected
        foreach ($expected as $index => $data) {
            foreach ($data['aliases'] as $alias => $properties) {
                if (!$this->isSearchName($alias)) {
                    $expected[$index]['aliases'][$this->getSearchName($alias)] = $expected[$index]['aliases'][$alias];

                    unset($expected[$index]['aliases'][$alias]);
                }
            }

            if (!$this->isSearchName($index)) {
                $expected[$this->getSearchName($index)] = $expected[$index];

                unset($expected[$index]);
            }
        }

        // Prepare Actual
        $actual = $this->app->make(Client::class)->indices()->getAlias();

        foreach ($actual as $index => $data) {
            if (!$this->isSearchName($index)) {
                unset($actual[$index]);
            }
        }

        // Compare
        $this->assertEquals($expected, $actual, $message);
    }

    protected function assertSearchIndexExists(string $expected, string $message = ''): void {
        $this->assertTrue(
            $this->app->make(Client::class)->indices()->exists([
                'index' => $this->getSearchName($expected),
            ]),
            $message,
        );
    }

    protected function assertSearchIndexAlias(
        string $expectedIndex,
        string $expectedAlias,
        string $message = '',
    ): void {
        $this->assertNotEmpty(
            $this->app->make(Client::class)->indices()->getAlias([
                'name'  => $expectedAlias,
                'index' => $expectedIndex,
            ]),
            $message,
        );
    }
    // </editor-fold>
}
