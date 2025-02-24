<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

set_time_limit(0);
ini_set('memory_limit', '4G');

use App\Exports\OrdersReportExport;
use App\Exports\OrdersRevenueReportExport;
use App\Helpers\ResponseError;
use App\Models\CategoryTranslation;
use App\Models\Order;
use App\Models\ProductTranslation;
use App\Repositories\CoreRepository;
use App\Repositories\ReportRepository\ChartRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderReportRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function ordersReportChart(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $type = data_get($filter, 'type');

        $keys  = ['count', 'price', 'quantity'];
        $chart = data_get($filter, 'chart');
        $chart = in_array($chart, $keys) ? $chart : 'count';

        $type = match ($type) {
            'year'  => '%Y',
            'week'  => '%w',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $orders = Order::when(data_get($filter, 'seller_id'), fn($query, $id) => $query->where('seller_id', $id))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('total_price as price'),
            ])
            ->get();

        $result = [];

        foreach ($orders as $order) {

            $time = data_get($order, 'time');

            if (data_get($result, $time)) {
                $result[$time]['count'] += 1;
                $result[$time]['price'] += data_get($order, 'price', 0);
                continue;
            }

            $result[$time] = [
                'time'  => $time,
                'count' => 1,
                'price' => data_get($order, 'price', 0),
            ];

        }

        $result = collect(array_values($result));

        $count     = max($result->sum('count'), 0);
        $price     = max($result->sum('price'), 0);
        $avgPrice  = $price > 0 && $count > 0 ? $price / $count : 0;
        $quantity  = max($result->sum('quantity'), 0);

        return [
            'chart'     => ChartRepository::chart($result, $chart),
            'currency'  => $this->currency,
            'count'     => $count,
            'price'     => $price,
            'avg_price' => $avgPrice,
            'quantity'  => $quantity,
        ];
    }

    /**
     * @param array $filter
     * @return array|Collection
     */
    public function ordersReportChartPaginate(array $filter): array|Collection
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $key    = data_get($filter, 'column', 'id');
        $column = data_get(['id', 'total_price'], $key, $key);

        $orders = Order::with([
            'user:id,firstname,lastname,active',
            'product.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])
                ->where('locale', $this->language),
        ])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if (data_get($filter, 'export') === 'excel') {

            $name = 'orders-report-products-' . Str::random(8);

            try {
                Excel::store(
                    new OrdersReportExport($orders->get()),
                    "export/$name.xlsx",
                    'public',
                    \Maatwebsite\Excel\Excel::XLSX
                );

                return [
                    'status'    => true,
                    'code'      => ResponseError::NO_ERROR,
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $orders = $orders->paginate(data_get($filter, 'perPage', 10));

        foreach ($orders as $i => $order) {

            /** @var Order $order */

            $result = [
                'id'        => $order->id,
                'firstname' => $order->user?->firstname,
                'lastname'  => $order->user?->lastname,
                'active'    => $order->user?->active,
                'price'     => $order->total_price,
                'product'   => $order->product?->translation?->title
            ];

            data_set($orders, $i, $result);
        }

        $isDesc = ($filter['sort'] ?? 'desc') === 'desc';

        return collect($orders)->sortBy($column, $isDesc ? SORT_DESC : SORT_ASC, $isDesc);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function revenueReport(array $filter): array
    {
        $type = data_get($filter, 'type');

        $type = match ($type) {
            'year'  => 'Y',
            'week'  => 'w',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $column   = [
            'count',
            'tax',
            'canceled_sum',
            'delivered_sum',
            'total_price',
            'total_quantity',
            'delivered_avg',
            'time',
        ];

        if (!in_array(data_get($filter, 'column'), $column)) {
            $filter['column'] = 'id';
        }

        $orders = Order::withSum([
                'orderDetails' => fn($q) => $q->select('id', 'order_id', 'quantity')
            ], 'quantity')
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->select([
                'created_at',
                'id',
                'total_price',
                'status',
                'created_at',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->get();

        $result = [];

        foreach ($orders as $order) {

            /** @var Order $order */

            $date = date($type, $order->created_at?->unix());

            $canceledPrice      = data_get($result, "$date.canceled_sum", 0);
            $deliveredPrice     = data_get($result, "$date.total_price", 0);

            $result[$date] = [
                'time'           => $date,
                'canceled_sum'   => $canceledPrice,
                'total_price'    => $deliveredPrice,
            ];

        }

        if (data_get($filter, 'export') === 'excel') {

            $name = 'report-revenue-' . Str::random(8);

            try {
                Excel::store(new OrdersRevenueReportExport($result), "export/$name.xlsx",'public');

                return [
                    'status'    => true,
                    'code'      => ResponseError::NO_ERROR,
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => $e->getMessage(),
                ];
            }

        }

        $result = collect($result);

        return [
            'paginate' => $result->values(),
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewCarts(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $type     = data_get($filter, 'type');
        $sellerId = data_get($filter, 'seller_id');

        $column = [
            'count',
            'tax',
            'canceled_sum',
            'delivered_sum',
            'delivered_avg',
            'time',
        ];

        if (!in_array(data_get($filter, 'column'), $column)) {
            $filter['column'] = 'time';
        }

        $chart = DB::table('orders')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
            ->select([
                DB::raw("sum(if(status = 'delivered', 1, 0)) as count"),
                DB::raw("sum(if(status = 'delivered', total_tax, 0)) as tax"),
                DB::raw("sum(if(status = 'canceled',  total_price, 0)) as canceled_sum"),
                DB::raw("sum(if(status = 'delivered', total_price, 0)) as delivered_sum"),
                DB::raw("avg(if(status = 'delivered', total_price, 0)) as delivered_avg"),
                DB::raw("(DATE_FORMAT(created_at, " . ($type == 'year' ? "'%Y" : ($type == 'month' ? "'%Y-%m" : "'%Y-%m-%d")) . "')) as time"),
            ])
            ->groupBy('time')
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->get();

        return [
            'chart_price'   => ChartRepository::chart($chart, 'delivered_sum'),
            'chart_count'   => ChartRepository::chart($chart, 'count'),
            'count'         => $chart->sum('count'),
            'tax'           => $chart->sum('tax'),
            'canceled_sum'  => $chart->sum('canceled_sum'),
            'delivered_sum' => $chart->sum('delivered_sum'),
            'delivered_avg' => $chart->sum('delivered_avg'),
        ];

    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewProducts(array $filter): array
    {
        [$dateFrom, $dateTo, $key] = $this->getKeys($filter);

        $column = data_get(['id', 'count', 'total_price', 'quantity'], $key, $key);

        if ($column == 'id') {
            $column = 'id';
        }

        $orders = Order::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select([
                DB::raw('sum(quantity) as quantity'),
                DB::raw('sum(total_price) as total_price'),
                DB::raw('count(id) as count'),
                DB::raw('product_id as id'),
            ])
            ->groupBy(['id'])
            ->having('count', '>', 0)
            ->orHaving('total_price', '>', 0)
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));

        $result = collect($orders->items())->transform(function ($item) {

            $item->title = ProductTranslation::where('product_id', $item->id)
                ->where('locale', $this->language)
                ->select(['id', 'product_id', 'locale', 'title'])
                ->first()
                ?->title;

            return $item;
        });

        return [
            'data' => $result,
            'meta' => [
                'last_page'  => $orders->lastPage(),
                'page'       => $orders->currentPage(),
                'total'      => $orders->total(),
                'more_pages' => $orders->hasMorePages(),
                'has_pages'  => $orders->hasPages(),
            ]
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewCategories(array $filter): array
    {
        [$dateFrom, $dateTo, $key, $sellerId] = $this->getKeys($filter);

        $column = data_get(['id', 'count', 'total_price', 'quantity'], $key, $key);

        if ($column == 'id') {
            $column = 'id';
        }

        $orderDetails = DB::table('products as p')
            ->crossJoin('orders as o', function ($builder) use ($dateFrom, $dateTo) {
                $builder
                    ->on('od.order_id', '=', 'o.id')
                    ->whereDate('o.created_at', '>=', $dateFrom)
                    ->whereDate('o.created_at', '<=', $dateTo);
            })
            ->when($sellerId, fn($q) => $q->where('p.seller_id', '=', $sellerId))
            ->select([
                DB::raw('sum(distinct od.quantity) as quantity'),
                DB::raw('sum(distinct o.total_price) as total_price'),
                DB::raw('count(distinct o.id) as count'),
                DB::raw('p.category_id as id'),
            ])
            ->groupBy(['id'])
            ->having('count', '>', '0')
            ->orHaving('total_price', '>', '0')
            ->orHaving('quantity', '>', '0')
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));

        $result = collect($orderDetails->items())->transform(function ($item) {

            $translation = CategoryTranslation::where('category_id', data_get($item, 'id'))
                ->where('locale', $this->language)
                ->select('title')
                ->first();

            $item->title = data_get($translation, 'title', 'EMPTY');

            return $item;
        });

        return [
            'data' => $result,
            'meta' => [
                'last_page'  => $orderDetails->lastPage(),
                'page'       => $orderDetails->currentPage(),
                'total'      => $orderDetails->total(),
                'more_pages' => $orderDetails->hasMorePages(),
                'has_pages'  => $orderDetails->hasPages(),
            ]
        ];
    }

    public function getKeys(array $filter): array
    {
        $dateFrom   = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo     = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
        $key        = data_get($filter, 'column', 'count');
        $sellerId   = data_get($filter, 'seller_id');

        return [$dateFrom, $dateTo, $key, $sellerId];
    }
}
