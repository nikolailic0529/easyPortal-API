<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Permission.
 *
 * @property string               $id
 * @property string               $key
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string          $description
 * @property-read string          $name
 * @method static PermissionFactory factory(...$parameters)
 * @method static Builder<Permission>|Permission newModelQuery()
 * @method static Builder<Permission>|Permission newQuery()
 * @method static Builder<Permission>|Permission query()
 */
class Permission extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'permissions';

    protected function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name', 'description'];
    }

    public function getNameAttribute(): string {
        return $this->key;
    }

    public function getDescriptionAttribute(): string {
        return $this->key;
    }
}
