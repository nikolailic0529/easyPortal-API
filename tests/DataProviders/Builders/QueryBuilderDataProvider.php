<?php declare(strict_types = 1);

namespace Tests\DataProviders\Builders;

use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\TestCase;

class QueryBuilderDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'Builder' => [
                new UnknownValue(),
                static function (TestCase $test): QueryBuilder {
                    return $test->app()->make('db')->table('tmp');
                },
            ],
        ]);
    }
}
