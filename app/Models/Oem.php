<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasDocuments;
use App\Utils\Eloquent\Concerns\TranslateProperties;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem.
 *
 * @property string                                                              $id
 * @property string                                                              $key
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $documents
 * @method static \Database\Factories\OemFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem query()
 * @mixin \Eloquent
 */
class Oem extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasDocuments;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'oems';

    public function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
