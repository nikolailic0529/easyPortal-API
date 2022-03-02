<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Models\Country;
use App\Models\Coverage;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Oem;
use App\Models\Permission;
use App\Models\QuoteRequestDuration;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type;
use App\Services\Service as BaseService;

class Service extends BaseService {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\I18n\Contracts\Translatable>>
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
     * @return array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\I18n\Contracts\Translatable>>
     */
    public function getTranslatableModels(): array {
        return static::$translatable;
    }
}
