<?php
declare(strict_types=1);

namespace App\Imports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrderImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable;

    private string $language;

    public function __construct(string $language)
    {
        $this->language = $language;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        foreach ($collection as $row) {

            $location   = explode(',', (string)data_get($row, 'location', ''));

            Order::updateOrCreate([
                'user_id'                => data_get($row,'user_id'),
                'username'               => data_get($row,'username'),
                'total_price'            => data_get($row,'total_price'),
                'currency_id'            => data_get($row,'currency_id'),
                'rate'                   => data_get($row,'rate'),
                'location'               => [
                    'latitude'  => data_get($location, 0),
                    'longitude' => data_get($location, 1),
                ],
                'address'                => data_get($row, 'address'),
                'phone'                  => data_get($row, 'phone'),
            ]);
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 10;
    }
}
