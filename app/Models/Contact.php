<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssetsOwners;
use App\Models\Relations\HasCustomersOwners;
use App\Models\Relations\HasTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contact.
 *
 * @property string                                                              $id
 * @property string                                                              $object_id
 * @property string                                                              $object_type
 * @property string|null                                                         $name
 * @property string|null                                                         $email
 * @property string|null                                                         $phone_number
 * @property bool|null                                                           $phone_valid
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type>          $types
 * @method static \Database\Factories\ContactFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact query()
 * @mixin \Eloquent
 */
class Contact extends PolymorphicModel {
    use HasFactory;
    use HasTypes;
    use HasCustomersOwners;
    use HasAssetsOwners;

    protected const CASTS = [
        'phone_valid' => 'bool',
    ] + parent::CASTS;

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
    protected $casts = self::CASTS;

    protected function getTypesPivot(): Pivot {
        return new ContactType();
    }
}
