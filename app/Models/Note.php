<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note.
 *
 * @property string                       $id
 * @property string                       $note
 * @property string                       $document_id
 * @property string                       $user_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\User             $user
 * @property \App\Models\Document         $document
 * @method static \Database\Factories\NoteFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note query()
 * @mixin \Eloquent
 */
class Note extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'notes';

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setUserAttribute(User $user): void {
        $this->user()->associate($user);
    }

    public function document(): BelongsTo {
        return $this->belongsTo(Document::class);
    }

    public function setDocumentAttribute(Document $document): void {
        $this->document()->associate($document);
    }
}
