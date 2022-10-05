<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Models\Data\Country;
use App\Models\Data\Coverage;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Permission;
use App\Models\QuoteRequestDuration;
use App\Services\I18n\Contracts\Translatable;
use App\Services\Service as BaseService;
use Illuminate\Database\Eloquent\Model;

class Service extends BaseService {
    /**
     * @var array<class-string<Model&Translatable>>
     */
    protected static array $translatable = [
        Country::class,
        Coverage::class,
        Currency::class,
        Language::class,
        Oem::class,
        Permission::class,
        QuoteRequestDuration::class,
        ServiceGroup::class,
        ServiceLevel::class,
        Status::class,
        Type::class,
    ];

    /**
     * @return array<class-string<Model&Translatable>>
     */
    public function getTranslatableModels(): array {
        return static::$translatable;
    }
}
