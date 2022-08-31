<?php declare(strict_types = 1);

namespace Tests\DataProviders\Builders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;

class EloquentBuilderDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'Builder' => [
                new UnknownValue(),
                static function (): EloquentBuilder {
                    return (new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        public $table = 'tmp';

                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
                         */
                        public $casts = [
                            'date_column'           => 'date',
                            'date_column_immutable' => 'immutable_date',
                        ];

                        /**
                         * @return BelongsTo<static, self>
                         */
                        public function parent(): BelongsTo {
                            return $this->belongsTo(static::class);
                        }
                    })->query();
                },
            ],
        ]);
    }
}
