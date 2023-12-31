<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocument;
use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasUser;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note.
 *
 * @property string                $id
 * @property string|null           $note
 * @property string                $document_id
 * @property string|null           $quote_request_id
 * @property string|null           $change_request_id
 * @property string                $organization_id
 * @property string                $user_id
 * @property bool                  $pinned
 * @property CarbonImmutable       $created_at
 * @property CarbonImmutable       $updated_at
 * @property CarbonImmutable|null  $deleted_at
 * @property User                  $user
 * @property ChangeRequest|null    $changeRequest
 * @property Document|null         $document
 * @property Collection<int, File> $files
 * @property Organization          $organization
 * @property QuoteRequest|null     $quoteRequest
 * @method static NoteFactory factory(...$parameters)
 * @method static Builder<Note>|Note newModelQuery()
 * @method static Builder<Note>|Note newQuery()
 * @method static Builder<Note>|Note query()
 */
class Note extends Model implements OwnedByOrganization {
    use HasFactory;
    use OwnedByOrganizationImpl;
    use SyncHasMany;
    use HasUser;
    use HasOrganization;
    use HasDocument;
    use HasFiles;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'notes';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'pinned' => 'bool',
    ];

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return BelongsTo<QuoteRequest, self>
     */
    public function quoteRequest(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function setQuoteRequestAttribute(QuoteRequest $request): void {
        $this->quoteRequest()->associate($request);
    }

    /**
     * @return BelongsTo<ChangeRequest, self>
     */
    public function changeRequest(): BelongsTo {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function setChangeRequestAttribute(ChangeRequest $request): void {
        $this->changeRequest()->associate($request);
    }
    // </editor-fold>
}
