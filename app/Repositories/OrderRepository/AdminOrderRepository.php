<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

use App\Models\Language;
use App\Models\Order;
use App\Repositories\CoreRepository;
use App\Traits\SetCurrency;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

class AdminOrderRepository extends CoreRepository
{
    use SetCurrency;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function ordersPaginate(array $filter = []): Paginator
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
            ->filter($filter)
            ->with([
                'user:id,lastname,firstname,img,email,phone',
                'transaction.paymentSystem',
                'currency',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function userOrdersPaginate(array $filter = []): Paginator
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
            ->filter($filter)
            ->with((new OrderRepository)->getWith($filter['user_id'] ?? null))
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $id
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function userOrder(string $id, array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return Order::with([
            'product.translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language),
        ])
            ->where('user_id', $id)
            ->groupBy(['product_id'])
            ->select([
                'product_id',
                DB::raw('count(product_id) as count'),
                DB::raw('sum(total_price) as total_price')
            ])
            ->orderBy('count', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }

}
