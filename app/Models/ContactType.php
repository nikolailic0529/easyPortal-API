<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Contact Type (pivot)
 *
 * @property string                       $id
 * @property string                       $contact_id
 * @property string                       $type_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ContactType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ContactType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ContactType query()
 * @mixin \Eloquent
 */
class ContactType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'contact_types';
}
