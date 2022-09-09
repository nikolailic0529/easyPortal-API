<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Type;
use App\Models\Relations\HasAssetsOwners;
use App\Models\Relations\HasCustomersOwners;
use App\Models\Relations\HasTypes;
use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\PolymorphicModel;
use Carbon\CarbonImmutable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contact.
 *
 * @property string                        $id
 * @property string                        $object_id
 * @property string                        $object_type
 * @property string|null                   $name
 * @property string|null                   $email
 * @property string|null                   $phone_number
 * @property bool|null                     $phone_valid
 * @property CarbonImmutable               $created_at
 * @property CarbonImmutable               $updated_at
 * @property CarbonImmutable|null          $deleted_at
 * @property-read Collection<int,Asset>    $assets
 * @property-read Collection<int,Customer> $customers
 * @property Collection<int,Type>          $types
 * @method static ContactFactory factory(...$parameters)
 * @method static Builder|Contact newModelQuery()
 * @method static Builder|Contact newQuery()
 * @method static Builder|Contact query()
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
