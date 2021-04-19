<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCurrency;
use App\Models\Concerns\HasCustomer;
use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasProduct;
use App\Models\Concerns\HasReseller;
use App\Models\Concerns\HasType;
use App\Models\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Document.
 *
 * @property string                                                                   $id
 * @property string                                                                   $oem_id
 * @property string                                                                   $type_id
 * @property string                                                                   $customer_id
 * @property string                                                                   $reseller_id
 * @property string                                                                   $number     Internal Number
 * @property string                                                                   $product_id Support Level
 * @property \Carbon\CarbonImmutable                                                  $start
 * @property \Carbon\CarbonImmutable                                                  $end
 * @property string|null                                                              $price
 * @property string                                                                   $currency_id
 * @property \Carbon\CarbonImmutable                                                  $created_at
 * @property \Carbon\CarbonImmutable                                                  $updated_at
 * @property \Carbon\CarbonImmutable|null                                             $deleted_at
 * @property \App\Models\Currency                                                     $currency
 * @property \App\Models\Customer                                                     $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry> $entries
 * @property-read int|null                                                            $entries_count
 * @property \App\Models\Oem                                                          $oem
 * @property \App\Models\Product                                                      $product
 * @property \App\Models\Reseller                                                     $reseller
 * @property \App\Models\Type                                                         $type
 * @method static \Database\Factories\DocumentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereOemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereResellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Document extends Model {
    use HasFactory;
    use HasOem;
    use HasType;
    use HasReseller;
    use HasCustomer;
    use HasCurrency;
    use HasProduct;

    protected const CASTS = [
        'start' => 'date',
        'end'   => 'date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'documents';

    public function entries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }

    /**
     * @inheritdoc
     */
    protected function getValidProductTypes(): array {
        return [ProductType::support()];
    }
}
