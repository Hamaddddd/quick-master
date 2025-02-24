<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Order;
use App\Models\Settings;
use App\Repositories\CoreRepository;
use App\Traits\SetCurrency;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class OrderRepository extends CoreRepository
{
    use SetCurrency;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    public function getWith(?int $userId = null): array
    {
        return [
            'user',
            'currency',
            'review' => fn($q) => $userId ? $q->where('user_id', $userId) : $q,
            'transaction.paymentSystem',
        ];
    }
    /**
     * @param array $filter
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function ordersList(array $filter = []): array|\Illuminate\Database\Eloquent\Collection
    {
        return $this->model()
            ->filter($filter)
            ->get();
    }

    /**
     * This is only for users route
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function ordersPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Order $order */
        $order = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $order
            ->filter($filter)
            ->with([
                'currency',
                'user:id,firstname,lastname,uuid,img,phone',
                'product:id,slug',
                'product.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'seller:id,firstname,lastname,uuid,img,phone',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @param int|null $seller
     * @param int|null $userId
     * @return Order|null
     */
    public function orderById(int $id, ?int $seller = null, ?int $userId = null): ?Order
    {
        return $this->model()
            ->with($this->getWith($userId))
            ->when($seller, fn($q) => $q->where('seller', $seller))
            ->find($id);
    }

    /**
     * @param int $id
     * @param int|null $sellerId
     * @param int|null $userId
     * @return Collection|null
     */
    public function ordersByParentId(int $id, ?int $sellerId = null, ?int $userId = null): ?Collection
    {
        return $this->model()
            ->with($this->getWith($userId))
            ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
            ->when($userId,   fn($q) => $q->where('user_id',   $userId))
            ->where(fn($q) => $q->where('id', $id)->orWhere('parent_id', $id))
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * @param int $id
     * @return Response|array
     */
    public function exportPDF(int $id): Response|array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $order = Order::with([
            'product.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language),
            'user:id,phone,firstname,lastname',
            'currency:id,symbol,position'
        ])->find($id);

        if (!$order) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ];
        }

        $logo = Settings::where('key', 'logo')->first()?->value;

        PDF::setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        $pdf = PDF::loadView('order-invoice', compact('order', 'logo'));

        /** @var Order $order */
        return $pdf->download("invoice-$order->id.pdf");
    }

    /**
     * @param int $id
     * @return Response|array
     */
    public function exportByParentPDF(int $id): Response|array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $orders = Order::with([
            'product.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language),
        ])
            ->where('id', $id)
            ->orWhere('parent_id', $id)
            ->get();

        if ($orders->count() === 0) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ];
        }

        $orders[0] = $orders[0]->loadMissing([
            'user:id,phone,firstname,lastname',
            'currency:id,symbol,position'
        ]);

        $logo = Settings::where('key', 'logo')->first()?->value;

        PDF::setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        $pdf = PDF::loadView('parent-order-invoice', compact('orders', 'logo'));

        $time = time();
        return $pdf->download("invoice-$time.pdf");
    }
}
