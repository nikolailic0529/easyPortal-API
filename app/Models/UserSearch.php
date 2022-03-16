<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasUser;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\UserSearchFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * User Search.
 *
 * @property string               $id
 * @property string               $user_id
 * @property string               $name
 * @property string               $key
 * @property string               $conditions
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property User                 $user
 * @method static UserSearchFactory factory(...$parameters)
 * @method static Builder|UserSearch newModelQuery()
 * @method static Builder|UserSearch newQuery()
 * @method static Builder|UserSearch query()
 * @mixin Eloquent
 */
class UserSearch extends Model {
    use HasFactory;
    use HasUser;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'user_searches';
}
