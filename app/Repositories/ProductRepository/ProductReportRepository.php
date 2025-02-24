<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

set_time_limit(0);
ini_set('memory_limit', '4G');

use App\Exports\ProductReportExport;
use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserActivity;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductReportRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $filter
     * @return array|Paginator
     */
    public function productReportPaginate(array $filter): Paginator|array
    {
        try {
            $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
            $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
            $locale   = Language::where('default', 1)->first(['locale', 'default'])?->locale;
            $sellerId = data_get($filter, 'seller_id');

            $data = Order::with([
                'product:id,active',
                'product.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->select('id', 'locale', 'product_id', 'title'),
            ])
                ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->select([
                    DB::raw('product_id'),
                    DB::raw('count(id) as count'),
                    DB::raw('sum(total_price) as total_price'),
                    DB::raw('sum(quantity) as quantity'),
                ])
                ->groupBy('product_id');

            if (data_get($filter, 'export') === 'excel') {

                $name = 'products-report-' . Str::random(8);

                $data = $data->get();

                Excel::store(
                    new ProductReportExport($data),
                    "export/$name.xlsx",
                    'public',
                    \Maatwebsite\Excel\Excel::XLSX
                );

                return [
                    'status' => true,
                    'code'   => ResponseError::NO_ERROR,
                    'data'   => [
                        'path'      => 'public/export',
                        'file_name' => "export/$name.xlsx",
                        'link'      => URL::to("storage/export/$name.xlsx"),
                    ]
                ];

            }

            $data = $data->paginate(data_get($filter, 'perPage', 10));

            $items = collect($data->items())->transform(function ($item) {

                $item->title    = $item->product?->translation?->title;
                $item->active   = $item?->active ? 'active' : 'inactive';

                unset($item['product']);
                unset($item['product_id']);

                return $item;
            });

            return [
                'data'       => $items,
                'last_page'  => $data->lastPage(),
                'page'       => $data->currentPage(),
                'total'      => $data->total(),
                'more_pages' => $data->hasMorePages(),
                'has_pages'  => $data->hasPages(),
            ];

        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function history(array $filter): LengthAwarePaginator
    {
        $agent = new Agent;

        $where = [
            'model_type' => Product::class,
            'device' => $agent->device(),
            'ip' => request()->ip(),
        ];

        if (data_get($filter, 'user_id')) {

            $where = [
                'model_type' => Product::class,
                'device'     => $agent->device(),
                'user_id'    => data_get($filter, 'user_id'),
            ];

        }

        $ids = UserActivity::where($where)
            ->orderBy('updated_at', 'desc')
            ->pluck('model_id')
            ->toArray();

        $implodeIds = implode(',', $ids);

        return Product::actual($this->language)
            ->whereIn('id', $ids)
            ->where('id', '!=', data_get($filter, 'id'))
            ->with((new RestProductRepository)->with())
            ->orderByRaw(DB::raw("FIELD(id, $implodeIds)"))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function mostPopulars(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $filter['model_type'] = Product::class;

        if (data_get($filter, 'date_from')) {
            $filter['date_from']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        }

        if (data_get($filter, 'date_to')) {
            $filter['date_to']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_to')));
        }

        return UserActivity::filter($filter)
            ->with([
                'model.translation' => fn($q) => $q
                    ->where('locale', $this->language)
            ])
            ->select([
                'model_type',
                'model_id',
                DB::raw('count(model_id) as count'),
            ])
            ->groupBy('model_id', 'model_type')
            ->orderBy('count', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }
}

