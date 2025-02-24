<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;

class RegionRelationsObserver
{
    /**
     * Handle the Brand "updated" event.
     *
     * @param Area $model
     * @return void
     */
    public static function area(Area $model): void
    {

    }

    public static function city(City $model): void
    {

    }

    public static function country(Country $model): void
    {

    }
}
