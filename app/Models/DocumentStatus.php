<?php declare(strict_types = 1);

namespace App\Models;

/**
 * DocumentStatusю
 *
 * @property string                       $id
 * @property string                       $document_id
 * @property string                       $status_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentStatus query()
 * @mixin \Eloquent
 */
class DocumentStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_statuses';
}
