<?php
declare(strict_types=1);

namespace App\Helpers;

class FilePaths
{
    public static function routes(): array
    {
        return [
            'ads' => [
                'rest'  => base_path('app/Addons/AdsPackage/routes/rest.php'),
                'user'  => base_path('app/Addons/AdsPackage/routes/user.php'),
                'admin' => base_path('app/Addons/AdsPackage/routes/admin.php'),
            ]
        ];
    }

    public static function adsExists(): bool
    {
        return file_exists(base_path('app/Addons/AdsPackage/Controllers/Rest/AdsProductPaginate.php'));
    }
}
