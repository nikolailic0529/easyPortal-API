<?php declare(strict_types = 1);

namespace Tests\DataProviders\Builders;

use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;

class BuilderDataProvider extends MergeDataProvider {
    public function __construct() {
        parent::__construct([
            'Query'    => new QueryBuilderDataProvider(),
            'Eloquent' => new EloquentBuilderDataProvider(),
        ]);
    }
}
