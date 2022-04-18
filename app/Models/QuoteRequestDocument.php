<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocument;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QuoteRequestDocument
 *
 * @property string                    $id
 * @property string                    $request_id
 * @property string                    $document_id
 * @property string                    $duration_id
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property CarbonImmutable|null      $deleted_at
 * @property Document                  $document
 * @property-read QuoteRequestDuration $duration
 * @property-read QuoteRequest         $request
 * @method static Builder|QuoteRequestDocument newModelQuery()
 * @method static Builder|QuoteRequestDocument newQuery()
 * @method static Builder|QuoteRequestDocument query()
 * @mixin \Eloquent
 */
class QuoteRequestDocument extends Model {
    use HasFactory;
    use HasDocument;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_request_documents';

    /**
     * @return BelongsTo<QuoteRequestDuration, self>
     */
    #[CascadeDelete(false)]
    public function duration(): BelongsTo {
        return $this->belongsTo(QuoteRequestDuration::class, 'duration_id');
    }

    /**
     * @return BelongsTo<QuoteRequest, self>
     */
    #[CascadeDelete(false)]
    public function request(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }
}