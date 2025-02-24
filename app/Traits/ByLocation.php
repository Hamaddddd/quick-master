<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Language;

/**
 * @property string|null $language
 */
trait ByLocation
{
    public function getWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        if (!isset($this->language)) {
            $this->language = $locale;
        }

        return [
            'region.translation' => fn($query) => $query
                ->where('locale', $this->language),
            'country.translation' => fn($query) => $query
                ->where('locale', $this->language),
            'city.translation' => fn($query) => $query
                ->where('locale', $this->language),
            'area.translation' => fn($query) => $query
                ->where('locale', $this->language),
        ];
    }
}
