<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File.
 *
 * @property string                                                              $id
 * @property string                                                              $name
 * @property string                                                              $disk
 * @property string                                                              $path
 * @property int                                                                 $size
 * @property string                                                              $type
 * @property string                                                              $hash
 * @property string                                                              $note_id
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \App\Models\Note                                                    $note
 * @method static \Database\Factories\FileFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File query()
 * @mixin \Eloquent
 */
class File extends Model {
    use HasFactory;
    use HasDocuments;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';

    public function note(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setNoteAttribute(Note $note): void {
        $this->note()->associate($note);
    }

    public function getUrlAttribute(): string {
        // Logic will be added to a resolver in another PR
        return $this->path;
    }
}
