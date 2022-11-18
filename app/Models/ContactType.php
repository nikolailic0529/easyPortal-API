<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ContactTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contact Type (pivot)
 *
 * @property string               $id
 * @property string               $contact_id
 * @property string               $type_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ContactTypeFactory factory(...$parameters)
 * @method static Builder<ContactType> newModelQuery()
 * @method static Builder<ContactType> newQuery()
 * @method static Builder<ContactType> query()
 */
class ContactType extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'contact_types';
}
