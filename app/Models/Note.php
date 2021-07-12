<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasDocument;
use App\Models\Concerns\SyncHasMany;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\File> $files
 * @method static \Database\Factories\NoteFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note query()
 * @mixin \Eloquent
 */
class Note extends Model {
    use HasFactory;
    use OwnedByOrganization;
    use SyncHasMany;
    use HasDocument;

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

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('organization_id');
    }

    public function files(): HasMany {
        return $this->hasMany(File::class);
    }

    public function setFilesAttribute(Collection|array $files): void {
        $this->syncHasMany('files', $files);
    }
}
