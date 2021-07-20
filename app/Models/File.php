<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File.
 *
 * @property string                       $id
 * @property string                       $name
 * @property string                       $disk
 * @property string                       $path
 * @property int                          $size
 * @property string                       $type
 * @property string                       $hash
 * @property string                       $note_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Note             $note
 * @method static \Database\Factories\FileFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File query()
 * @mixin \Eloquent
 */

 use function app;

class File extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';

    public function note(): BelongsTo {
        return $this->belongsTo(Note::class);
    }

    public function setNoteAttribute(Note $note): void {
        $this->note()->associate($note);
    }

    public function getUrlAttribute(): string {
        return app()->make(UrlGenerator::class)->route('files', ['id' => $this->getKey() ]);
    }
}