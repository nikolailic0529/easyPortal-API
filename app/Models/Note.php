<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocument;
use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOrganizationNullable;
use App\Models\Relations\HasUser;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\CascadeDeletes\CascadeDeletable;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Note.
 *
 * @property string                                                     $id
 * @property string                                                     $note
 * @property string                                                     $document_id
 * @property string                                                     $organization_id
 * @property string                                                     $user_id
 * @property bool                                                       $pinned
 * @property \Carbon\CarbonImmutable                                    $created_at
 * @property \Carbon\CarbonImmutable                                    $updated_at
 * @property \Carbon\CarbonImmutable|null                               $deleted_at
 * @property \App\Models\User                                           $user
 * @property \App\Models\Document                                       $document
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\File> $files
 * @property \App\Models\Organization                                   $organization
 * @method static \Database\Factories\NoteFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note query()
 * @mixin \Eloquent
 */
class Note extends Model implements CascadeDeletable {
    use HasFactory;
    use OwnedByOrganization;
    use SyncHasMany;
    use HasUser;
    use HasOrganizationNullable;
    use HasDocument;
    use HasFiles;

    protected const CASTS = [
        'pinned' => 'bool',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'notes';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool {
        return $name === 'files';
    }
}
