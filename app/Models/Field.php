<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasQuotes;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\PolymorphicModel;
use Carbon\CarbonImmutable;
use Database\Factories\FieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
class Field extends PolymorphicModel implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'types';

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
