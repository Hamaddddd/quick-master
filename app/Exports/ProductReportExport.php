<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductReportExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(protected mixed $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn(Order $order) => $this->tableBody($order));
    }

    public function headings(): array
    {
        return [
            'Product title',
            'Net sales',
            'Category',
            'Status',
        ];
    }

    private function tableBody(Order $order): array
    {
        $product = $order->product;

        return [
            'title'    => $product?->translation?->title,
            'sum'      => $order->total_price ?? 0,
            'category' => $product?->category?->translation?->title,
            'status'   => $product?->active ? 'active' : 'inactive',
        ];
    }
}
