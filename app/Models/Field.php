<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\FieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Field.
 *
 * @property string               $id
 * @property string               $object_type
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static FieldFactory factory(...$parameters)
 * @method static Builder|Field newModelQuery()
 * @method static Builder|Field newQuery()
 * @method static Builder|Field query()
 */
class Field extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'fields';

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return HasManyThrough<Document>
     */
    #[CascadeDelete(false)]
    public function documents(): HasManyThrough {
        return $this->hasManyThrough(
            Document::class,
            DocumentEntryField::class,
            null,
            (new Document())->getKeyName(),
            null,
            'document_id',
        );
    }

    /**
     * @return HasManyThrough<Document>
     */
    #[CascadeDelete(false)]
    public function contracts(): HasManyThrough {
        // fixme(Models): Use HasContracts (https://github.com/fakharanwar/easyPortal-API/issues/995)
        return $this
            ->documents()
            ->where(static function (Builder|HasManyThrough $builder): void {
                /** @var Builder<Document>|HasManyThrough<Document> $builder */
                $builder->queryContracts();
            });
    }

    /**
     * @return HasManyThrough<Document>
     */
    #[CascadeDelete(false)]
    public function quotes(): HasManyThrough {
        // fixme(Models): Use HasQuotes (https://github.com/fakharanwar/easyPortal-API/issues/995)
        return $this
            ->documents()
            ->where(static function (Builder|HasManyThrough $builder): void {
                /** @var Builder<Document>|HasManyThrough<Document> $builder */
                $builder->queryQuotes();
            });
    }
    // </editor-fold>

    // <editor-fold desc="Translatable">
    // =========================================================================
    protected function getTranslatableKey(): ?string {
        return "{$this->object_type}/{$this->key}";
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
    // </editor-fold>
}
