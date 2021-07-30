<?php declare(strict_types = 1);

namespace App\Models;

/**
 * App\Models\QuoteRequestStatus
 *
 * @property string                       $id
 * @property string                       $request_id
 * @property string                       $status_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestStatus query()
 * @mixin \Eloquent
 */
class QuoteRequestStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_request_statuses';
}
