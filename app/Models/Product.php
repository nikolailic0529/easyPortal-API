<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * Product.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $type_id
 * @property string                       $sku
 * @property string                       $name
 * @property \Carbon\CarbonImmutable|null $eol
 * @property \Carbon\CarbonImmutable|null $eos
 * @property string|null                  $description
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Oem              $oem
 * @property \App\Models\Type             $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereEol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereEos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Product extends Model {
    use HasFactory;

    protected const CASTS = [
        'eol' => 'date',
        'eos' => 'date',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    public function oem(): BelongsTo {
        return $this->belongsTo(Oem::class);
    }

    public function setOemAttribute(Oem $oem): void {
        $this->oem()->associate($oem);
    }

    public function type(): BelongsTo {
        return $this->belongsTo(Type::class);
    }

    public function setTypeAttribute(Type $type): void {
        if ($type->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException(sprintf(
                'The `$type` related to `%s`, `%s` required.',
                $type->object_type,
                $this->getMorphClass(),
            ));
        }

        $this->type()->associate($type);
    }
}
