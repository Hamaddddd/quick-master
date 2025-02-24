<?php
declare(strict_types=1);

namespace App\Services\TransactionService;

use App\Addons\AdsPackage\Models\AdsPackage;
use App\Addons\AdsPackage\Models\UserAdsPackage;
use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class TransactionService extends CoreService
{
    protected function getModelClass(): string
    {
        return Transaction::class;
    }

    public function orderTransaction(int $id, array $data, object|string|null $class = Order::class): array
    {
        /** @var Order $order */
        $order = $class::with(['user'])->find($id);

        if (!$order) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $payment = $this->checkPayment(data_get($data, 'payment_sys_id'), $order, true);

        if (!data_get($payment, 'status')) {
            return $payment;
        }

        if (data_get($payment, 'already_payed')) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
        }

        /** @var Transaction $transaction */
        $transaction = $order->createTransaction([
            'price'                 => $order->total_price,
            'user_id'               => $order->user_id,
            'payment_sys_id'        => data_get($data, 'payment_sys_id'),
            'payment_trx_id'        => data_get($data, 'payment_trx_id'),
            'note'                  => $order->id,
            'perform_time'          => now(),
            'status_description'    => 'Transaction for order #' . $order->id
        ]);

        if (data_get($payment, 'wallet')) {
            $this->walletHistoryAdd($order->user, $transaction, $order);
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

    public function walletTransaction(int $id, array $data): array
    {
        $wallet = Wallet::find($id);

        if (empty($wallet)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $wallet->createTransaction([
            'price'                 => data_get($data, 'price'),
            'user_id'               => data_get($data, 'user_id'),
            'payment_sys_id'        => data_get($data, 'payment_sys_id'),
            'payment_trx_id'        => data_get($data, 'payment_trx_id'),
            'note'                  => $wallet->id,
            'perform_time'          => now(),
            'status_description'    => "Transaction for wallet #$wallet->id"
        ]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $wallet];
    }

    public function adsTransaction(int $id, array $data): array
    {
        $ads = AdsPackage::find($id);

        if (empty($ads)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $request = request()->merge([
            'user_id'     => auth('sanctum')->id(),
            'total_price' => $ads->price,
        ]);

        $payment = $this->checkPayment(data_get($data, 'payment_sys_id'), $request);

        if (!data_get($payment, 'status')) {
            return $payment;
        }

        $userAds = UserAdsPackage::create([
            'ads_package_id' => $ads->id,
            'category_id'    => $ads->category_id,
            'count'          => $ads->count,
            'time_type'      => $ads->time_type,
            'time'           => $ads->time,
            'price'          => $ads->price,
        ]);

        $userAds->createTransaction([
            'price'              => $userAds->price,
            'user_id'            => auth('sanctum')->id(),
            'payment_sys_id'     => data_get($data, 'payment_sys_id'),
            'payment_trx_id'     => data_get($data, 'payment_trx_id'),
            'note'               => $ads->id,
            'perform_time'       => now(),
            'status'             => Transaction::STATUS_PAID,
            'status_description' => "Transaction for Ads #$ads->id"
        ]);

        if (data_get($payment, 'wallet')) {
            $this->walletHistoryAdd(
                auth('sanctum')->user(),
                $userAds->transaction,
                $userAds,
                'Ads',
                'withdraw'
            );
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $ads];
    }

    private function checkPayment(int $id, Model|Order|Request $model, bool $isOrder = false): array
    {
        $payment = Payment::where('active', 1)->find($id);

        if (!$payment) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        } elseif ($payment->tag !== 'wallet') {
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'payment_tag' => $payment->tag];
        }

        if ($isOrder) {

            /** @var Order $model */
            $changedPrice = data_get($model, 'total_price', 0) - $model->transaction?->price;

            if ($model->transaction?->status === Transaction::STATUS_PAID && $changedPrice <= 1) {
                return ['status' => true, 'code' => ResponseError::NO_ERROR, 'already_payed' => true];
            }

            data_set($model, 'total_price', $changedPrice);
        }

        /** @var User $user */
        $user = User::with('wallet')->find(data_get($model, 'user_id'));

        if (empty($user?->wallet)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_109,
                'message' => __('errors.' . ResponseError::ERROR_109, locale: $this->language)
            ];
        }

        $ratePrice = data_get($model, 'total_price', 0);

        if ($user->wallet->price >= $ratePrice) {

            $user->wallet()->update(['price' => $user->wallet->price - $ratePrice]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'wallet' => $user->wallet];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_109,
            'message' => __('errors.' . ResponseError::ERROR_109, locale: $this->language)
        ];
    }

    private function walletHistoryAdd(
        ?User $user,
        Transaction $transaction,
        Model|Order $model,
        ?string $type = 'Order',
        ?string $paymentType = 'topup'
    ): void
    {
        $modelId = $model->id;

        $user->wallet->histories()->create([
            'uuid'           => Str::uuid(),
            'transaction_id' => $transaction->id,
            'type'           => $paymentType,
            'price'          => $transaction->price,
            'note'           => "Payment $type #$modelId via Wallet" ,
            'status'         => Transaction::STATUS_PAID,
            'created_by'     => $transaction->user_id,
        ]);

        $transaction->update(['status' => Transaction::STATUS_PAID]);

    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateStatus(int $id, array $data = []): array
    {
        try {
            $transaction = Transaction::with(['payable'])
                ->when(isset($data['seller_id']), function ($q) use ($data) {
                    $q->whereHasMorph('payable', Order::class, fn($q) => $q->where('seller_id', $data['seller_id']));
                })
                ->find($id);

            if (empty($transaction)) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
                ];
            }

            $transaction->update(['status' => $data['status']]);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage(),
            ];
        }
    }
}
