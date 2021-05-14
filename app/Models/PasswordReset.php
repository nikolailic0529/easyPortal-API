<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Password Reset.
 *
 * @property string                       $id
 * @property string                       $email
 * @property string                       $token
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\PasswordResetFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PasswordReset whereUpdatedAt($value)
 * @mixin \Eloquent
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
