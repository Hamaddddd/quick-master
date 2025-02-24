<?php
declare(strict_types=1);

namespace App\Repositories\ReportRepository;

use App\Models\Order;
use App\Models\Transaction;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HistoryRepository
{
    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        return Order::filter($filter)
            ->with([
                'transaction:id,payable_id,payable_type,status,payment_sys_id',
                'transaction.paymentSystem:id,tag'
            ])
            ->select([
                'id',
                'user_id',
                'total_price',
                'created_at',
                'note',
            ])
            ->when($filter['type'] === 'today', fn($query) =>
                $query->where('created_at', '>=', now()->format('Y-m-d 00:00:01'))
            )
            ->orderBy(data_get($filter, 'column', 'created_at'), data_get($filter, 'sort', 'asc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function cards(array $filter): array
    {
        $cash    = 0;
        $wallet  = 0;
        $other   = 0;

        Order::with([
            'transaction:id,payable_id,payable_type,status',
            'transaction.paymentSystem:id,tag',
        ])
            ->whereHas('transaction', fn($q) => $q->where('status', Transaction::STATUS_PAID))
            ->select([
                'total_price'
            ])
            ->chunkMap(function (Order $order) use (&$cash, &$wallet, &$other) {
                switch (true) {
                    case $order->transaction?->paymentSystem?->tag === 'cash':
                        $cash += $order->total_price;
                        break;
                    case $order->transaction?->paymentSystem?->tag === 'wallet':
                        $wallet += $order->total_price;
                        break;
                    default:
                        $other += $order->total_price;
                        break;
                }
            });

        return [
            'cash'   => $cash,
            'other'  => $other,
            'wallet' => $wallet,
        ];
    }

    public function mainCards(array $filter): array
    {
        $dateFrom   = $filter['date_from'];
        $dateTo     = $filter['date_to'];

        $days       = (round((strtotime($dateFrom) - strtotime($dateTo)) / (60 * 60 * 24)) + 1) * 2;

        $prevFrom = date('Y-m-d 00:00:01', strtotime("$dateFrom $days days"));

        $prevTo   = date('Y-m-d 23:59:59', strtotime($dateTo));

        $curFrom  = date('Y-m-d 00:00:01', strtotime($dateFrom));

        $prevPeriod = DB::table('orders')
            ->where('seller_id', $filter['seller_id'])
            ->where('created_at', '>=', $prevFrom)
            ->where('created_at', '<=', $curFrom)
            ->select([
                DB::raw('sum(total_price) as orders'),
            ])
            ->first();

        $curPeriod = DB::table('orders')
            ->where('seller_id', $filter['seller_id'])
            ->where('created_at', '>=', $curFrom)
            ->where('created_at', '<=', $prevTo)
            ->select([
                DB::raw('sum(total_price) as orders'),
            ])
            ->first();

        $revenue        = (int)data_get($curPeriod, 'revenue');
        $prevRevenue    = (int)data_get($prevPeriod, 'revenue');

        $orders         = (int)data_get($curPeriod, 'orders');
        $prevOrders     = (int)data_get($prevPeriod, 'orders');

        $average        = (int)data_get($curPeriod, 'average');
        $prevAverage    = (int)data_get($prevPeriod, 'average');

        $revenuePercent = $revenue - $prevRevenue;
        $revenuePercent = $revenuePercent > 1 && $prevRevenue > 1 ? $revenuePercent / $prevRevenue * 100 : 100;

        $ordersPercent  = $orders - $prevOrders;
        $ordersPercent  = $ordersPercent > 1 && $prevOrders > 1 ? $ordersPercent / $prevOrders * 100 : 100;

        $averagePercent = $average - $prevAverage;
        $averagePercent = $averagePercent > 1 && $prevAverage > 1 ? $averagePercent / $prevAverage * 100 : 100;

        return [
            'revenue'               => $revenue,
            'revenue_percent'       => $revenue <= 0 ? 0 : $revenuePercent,
            'revenue_percent_type'  => $revenuePercent <= 0 ? 'minus' : 'plus',
            'orders'                => $orders,
            'orders_percent'        => $orders <= 0 ? 0 : $ordersPercent,
            'orders_percent_type'   => $ordersPercent <= 0 ? 'minus' : 'plus',
            'average'               => $average,
            'average_percent'       => $average <= 0 ? 0 : $averagePercent,
            'average_percent_type'  => $averagePercent <= 0 ? 'minus' : 'plus',
        ];
    }

    public function chart(array $filter): Collection
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter,'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter,'date_to', now()->toString())));

        $type = match ($filter['type']) {
            'year'  => '%Y',
            'week'  => '%Y-%m-%d %w',
            'month' => '%Y-%m-%d',
            'day'   => '%Y-%m-%d %H:00',
        };

        return DB::table('orders')
            ->where('seller_id', $filter['seller_id'])
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('sum(total_price) as total_price'),
            ])
            ->groupBy('time')
            ->orderBy('time')
            ->get();
    }

}
