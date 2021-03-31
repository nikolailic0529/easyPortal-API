<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contact.
 *
 * @property string                                                     $id
 * @property string                                                     $object_id
 * @property string                                                     $object_type
 * @property string|null                                                $name
 * @property string|null                                                $email
 * @property string|null                                                $phone_number
 * @property bool|null                                                  $phone_valid
 * @property \Carbon\CarbonImmutable                                    $created_at
 * @property \Carbon\CarbonImmutable                                    $updated_at
 * @property \Carbon\CarbonImmutable|null                               $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type> $types
 * @property-read int|null                                              $types_count
 * @method static \Database\Factories\ContactFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact wherePhoneValid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Contact extends PolymorphicModel {
    use HasFactory;
    use HasTypes;

    protected const CASTS = [
        'phone_valid' => 'bool',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;
}
