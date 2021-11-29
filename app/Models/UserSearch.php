<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasUser;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * User Search.
 *
 * @property string                       $id
 * @property string                       $user_id
 * @property string                       $name
 * @property string                       $key
 * @property string                       $conditions
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\User             $user
 * @method static \Database\Factories\UserSearchFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserSearch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserSearch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserSearch query()
 * @mixin \Eloquent
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
