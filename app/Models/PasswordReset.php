<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\PasswordResetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Password Reset.
 *
 * @property string               $id
 * @property string               $email
 * @property string               $token
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static PasswordResetFactory factory(...$parameters)
 * @method static Builder<PasswordReset> newModelQuery()
 * @method static Builder<PasswordReset> newQuery()
 * @method static Builder<PasswordReset> query()
 */
class PasswordReset extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'password_resets';
}
