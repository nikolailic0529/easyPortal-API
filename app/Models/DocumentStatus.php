<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * DocumentStatus.
 *
 * @property string               $id
 * @property string               $document_id
 * @property string               $status_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static DocumentStatusFactory factory(...$parameters)
 * @method static Builder<DocumentStatus>|DocumentStatus newModelQuery()
 * @method static Builder<DocumentStatus>|DocumentStatus newQuery()
 * @method static Builder<DocumentStatus>|DocumentStatus query()
 */
class DocumentStatus extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_statuses';
}
