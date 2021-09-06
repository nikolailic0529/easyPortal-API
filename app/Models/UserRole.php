<?php declare(strict_types = 1);

namespace App\Models;

/**
 * User Role (pivot)
 *
 * @property string                       $id
 * @property string                       $user_id
 * @property string                       $role_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserRole query()
 * @mixin \Eloquent
 */
class UserRole extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'user_roles';
}
