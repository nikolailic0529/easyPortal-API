<?php declare(strict_types = 1);

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Model;

interface ScopeWithMetadata extends Scope {
    public function getSearchProperty(Model $model): string;

    public function getSearchMetadataProperty(Model $model): string;

    public function getSearchMetadataValue(Model $model): mixed;
}
