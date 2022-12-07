<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Type;
use App\Models\Relations\HasObject;
use App\Models\Relations\HasTypes;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contact.
 *
 * @property string               $id
 * @property string               $object_id
 * @property string               $object_type
 * @property string|null          $name
 * @property string|null          $email
 * @property string|null          $phone_number
 * @property bool|null            $phone_valid
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Collection<int,Type> $types
 * @method static ContactFactory factory(...$parameters)
 * @method static Builder<Contact>|Contact newModelQuery()
 * @method static Builder<Contact>|Contact newQuery()
 * @method static Builder<Contact>|Contact query()
 */
class Contact extends Model {
    use HasFactory;
    use HasTypes;
    use HasObject;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'phone_valid' => 'bool',
    ];

    protected function getTypesPivot(): Pivot {
        return new ContactType();
    }
}
