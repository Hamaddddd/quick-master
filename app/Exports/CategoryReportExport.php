<?php
declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryReportExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection|array $rows)
    {
    }

    public function collection(): Collection|array
    {
        return $this->rows->map(fn(Collection|array $row) => $this->tableBody($row));
    }

    public function headings(): array
    {
        return [
            'Category',
            'Net sales',
            'Products',
        ];
    }

    private function tableBody(Collection|array $row): array
    {
        return [
            'category'       => data_get($row, 'title'),
            'price'          => data_get($row, 'price'),
            'products_count' => data_get($row, 'products_count'),
        ];
    }
}
