<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Duration.
 *
 * @property string                                                           $id
 * @property string                                                           $duration
 * @property \Carbon\CarbonImmutable                                          $created_at
 * @property \Carbon\CarbonImmutable                                          $updated_at
 * @property \Carbon\CarbonImmutable|null                                     $deleted_at
 * @method static \Database\Factories\DurationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Duration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Duration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Duration query()
 * @mixin \Eloquent
 */
class Duration extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'durations';

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['duration'];
    }
}
