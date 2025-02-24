<?php
declare(strict_types=1);

namespace App\Imports;

use App\Models\Language;
use App\Models\Product;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProductImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable;

    private string $language;

    public function __construct(string $language)
    {
        $this->language = $language;
    }

    /**
     * @param Collection $collection
     * @return void
     * @throws Throwable
     */
    public function collection(Collection $collection): void
    {
        $language = Language::where('default', 1)->first();

        foreach ($collection as $row) {

            DB::transaction(function () use ($row, $language) {

                $data = [
                    'category_id' => data_get($row, 'category_id'),
                    'brand_id'    => data_get($row, 'brand_id'),
                    'unit_id'     => data_get($row, 'unit_id'),
                    'keywords'    => data_get($row, 'keywords', ''),
                    'active'      => data_get($row, 'active') === 'active' ? 1 : 0,
                    'qr_code'     => data_get($row, 'qr_code', ''),
                    'status'      => in_array(data_get($row, 'status'), Product::STATUSES) ? data_get($row, 'status') : Product::PENDING,
                    'age_limit'   => $row['age_limit']  ?? 0,
                    'visibility'  => $row['visibility'] ?? 1,
                    'price'       => $row['price']  ?? 0,
                ];

                try {
                    $product = Product::updateOrCreate($data);
                } catch (Throwable) {
                    return;
                }

                $this->downloadImages($product, data_get($row, 'img_urls', ''));

                // Translation
                if (!empty(data_get($row, 'product_title'))) {

                    $product->translation()->updateOrInsert([
                        'product_id' => $product->id,
                        'locale'     => $this->language ?? $language
                    ], [
                       'title'       => data_get($row, 'product_title', ''),
                       'description' => data_get($row, 'product_description', '')
                    ]);
                }

            });

        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

}
