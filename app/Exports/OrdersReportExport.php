<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Order;
use App\Traits\Loggable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection,
    ShouldAutoSize,
    WithBatchInserts,
    WithChunkReading,
    WithHeadings,
    WithMapping,
};

class OrdersReportExport implements FromCollection, WithMapping, ShouldAutoSize, WithBatchInserts, WithChunkReading, WithHeadings
{
    use Loggable;

    private Collection $rows;

    /**
     * Export constructor.
     *
     * @param Collection $rows
     */
    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    public function moneyFormatter($number): string
    {
        [$whole, $decimal] = sscanf($number, '%d.%d');

        $money = number_format($number, 0, ',', ' ');

        return $decimal ? "$money,$decimal" : $money;
    }

    public function map($row): array
    {
        /** @var Order $row */
        return [
            $row->created_at,
            $row->id,
            data_get($row->user, 'firstname') . ' ' . data_get($row->user, 'lastname'),
            $row->product?->translation?->title,
            $this->moneyFormatter($row->total_price)
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            '#',
            'Customer',
            'Products',
            'Net sales',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
