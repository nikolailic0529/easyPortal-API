<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Properties\Property;
use Closure;

/**
 * Required to phpstan.
 */
interface Searchable {
    public function searchable(): void;

    public function unsearchable(): void;

    public function searchableAs(): string;

    public function setSearchableAs(?string $searchableAs): static;

    public function getSearchableAsDefault(): string;

    public function shouldBeSearchable(): bool;

    public function getSearchConfiguration(): Configuration;

    /**
     * @return SearchBuilder<static>
     */
    public static function search(string $query = '', Closure $callback = null): SearchBuilder;

    public static function isSearchSyncingEnabled(): bool;

    public static function enableSearchSyncing(): void;

    public static function disableSearchSyncing(): void;

    /**
     * Returns properties that must be added to the index.
     *
     * *Warning:* If array structure is changed the search index MUST be rebuilt.
     *
     * Should return array where:
     * - `key`   - key that will be used in index;
     * - `value` - property name (not value!) that may contain dots to get
     *             properties from related models, OR array with properties;
     *
     * Example:
     *      [
     *          'name' => new Text('name', true),         // $model->name
     *          'product' => [
     *              'sku'  => new Text('product.sku'),    // $model->product->sku
     *              'name' => new Text('product.name'),   // $model->product->name
     *          ],
     *      ]
     *
     * @return array<string,Property>
     */
    public static function getSearchProperties(): array;

    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @see getSearchProperties()
     *
     * @return array<string,Property>
     */
    public static function getSearchMetadata(): array;
}
