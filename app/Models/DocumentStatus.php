<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * DocumentStatusю
 *
 * @property string               $id
 * @property string               $document_id
 * @property string               $status_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|DocumentStatus newModelQuery()
 * @method static Builder|DocumentStatus newQuery()
 * @method static Builder|DocumentStatus query()
 */
class DocumentStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_statuses';
}
