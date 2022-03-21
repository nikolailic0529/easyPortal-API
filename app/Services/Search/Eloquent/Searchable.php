<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
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

    public static function search(string $query = '', Closure $callback = null): SearchBuilder;

    public static function isSearchSyncingEnabled(): bool;

    public static function enableSearchSyncing(): void;

    public static function disableSearchSyncing(): void;
}
