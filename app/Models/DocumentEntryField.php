<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasField;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentEntryFieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string               $id
 * @property string               $document_entry_id
 * @property string               $field_id
 * @property string|null          $value
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static DocumentEntryFieldFactory factory(...$parameters)
 * @method static Builder|ResellerLocationType newModelQuery()
 * @method static Builder|ResellerLocationType newQuery()
 * @method static Builder|ResellerLocationType query()
 */
class DocumentEntryField extends Model {
    use HasFactory;
    use HasField;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_entry_fields';
}
