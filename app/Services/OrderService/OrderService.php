<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Services\CoreService;
use App\Services\TransactionService\TransactionService;
use App\Traits\Notification;
use DB;
use Exception;
use Throwable;

class OrderService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Order::class;
    }

    private function with(): array
    {
        return [
            'user',
            'user',
            'review',
            'currency',
            'transaction.paymentSystem',
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            $orders = DB::transaction(function () {
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $orders
            ];

        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'code'    => ResponseError::ERROR_501,
            ];
        }
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            /** @var Order $order */
            $order = DB::transaction(function () use ($data, $id) {

                /** @var Order $order */
                $order = $this->model()
                    ->with(['transaction'])
                    ->find($id);

                if (!$order) {
                    throw new Exception(__('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language));
                }

                $order->update($data);

                if (data_get($data, 'payment_id') && !data_get($data, 'trx_status')) {

                    $data['payment_sys_id'] = data_get($data, 'payment_id');

                    $result = (new TransactionService)->orderTransaction($order->id, $data);

                    if (!data_get($result, 'status')) {
                        throw new Exception(data_get($result, 'message'));
                    }

                }

                return $order;
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $order->fresh($this->with())
            ];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'code'    => ResponseError::ERROR_502
            ];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $id
     * @return array
     */
    public function destroy(?array $ids = [], ?int $id = null): array
    {
        $errors = [];

        $orders = Order::with(['product'])
            ->when($id, fn($q) => $q->where('user_id', $id))
            ->find((array)$ids);

        foreach ($orders as $order) {

            try {
                DB::transaction(function () use ($order) {

                    /** @var Order $order */
                    DB::table('push_notifications')
                        ->where('model_type', Order::class)
                        ->where('model_id', $order->id)
                        ->delete();

                    $order->user->update([
                        'o_count' => $order->user->o_count - 1,
                        'o_sum'   => $order->user->o_sum - $order->total_price,
                    ]);

                    $order->delete();

                });
            } catch (Throwable $e) {
                $errors[] = $order->id;

                $this->error($e);
            }

        }

        return $errors;
    }

}
