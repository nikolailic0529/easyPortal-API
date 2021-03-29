<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization.
 *
 * @property string                                                                 $id
 * @property string|null                                                            $subdomain
 * @property string                                                                 $name
 * @property \Carbon\CarbonImmutable                                                $created_at
 * @property \Carbon\CarbonImmutable                                                $updated_at
 * @property \Carbon\CarbonImmutable|null                                           $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|array<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection|array<\App\Models\Location>   $locations
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereSubdomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Organization extends Model {
    use HasFactory;

    public const ROOT = '@root';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organizations';

    public function isRoot(): bool {
        return $this->subdomain === self::ROOT;
    }
}
