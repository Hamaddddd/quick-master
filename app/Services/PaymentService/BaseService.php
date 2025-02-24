<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use Log;
use Exception;
use Throwable;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\Transaction;
use App\Traits\Notification;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Helpers\ResponseError;
use App\Models\PaymentProcess;
use App\Addons\AdsPackage\Models\AdsPackage;
use App\Addons\AdsPackage\Models\UserAdsPackage;
use App\Services\WalletHistoryService\WalletHistoryService;

class BaseService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Payment::class;
    }

    public function afterHook($token, $status, ?string $secondToken = null): void
    {
        try {
            $paymentProcess = PaymentProcess::with(['model', 'user'])->where('id', $token)->first();

            if (empty($paymentProcess)) {
                $paymentProcess = PaymentProcess::with(['model', 'user'])->where('id', $secondToken)->first();
            }

            if (empty($paymentProcess)) {
                return;
            }

            /** @var PaymentProcess $paymentProcess */
            if ($paymentProcess->model_type === Wallet::class && $status === Transaction::STATUS_PAID) {

                $totalPrice = (double)data_get($paymentProcess->data, 'total_price') / 100;

                $user = $paymentProcess->user;

                (new WalletHistoryService)->create([
                    'type'           => 'topup',
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'created_by'     => data_get($paymentProcess->data, 'created_by'),
                    'payment_trx_id' => $token,
                    'price'          => $totalPrice,
                    'note'           => __('errors.' . ResponseError::WALLET_TOP_UP, ['sender' => ''], $user?->lang ?? $this->language),
                    'status'         => WalletHistory::PAID,
                    'user'           => $user
                ]);

                return;
            }

            if ($paymentProcess->model_type === AdsPackage::class) {

                $adsPackage = $paymentProcess->model;

                if ($status === Transaction::STATUS_PAID) {
                    $model = UserAdsPackage::create([
                        'ads_package_id' => $adsPackage->id,
                        'user_id'        => $paymentProcess->user_id,
                        'category_id'    => $adsPackage->category_id,
                        'count'          => $adsPackage->count,
                        'time_type'      => $adsPackage->time_type,
                        'time'           => $adsPackage->time,
                        'price'          => $adsPackage->price,
                    ]);

                    $model->createTransaction([
                        'price'          => $paymentProcess->model?->adsPackage?->price ?? 1,
                        'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                        'user_id'        => $paymentProcess->user_id,
                        'payment_trx_id' => $token,
                        'status'         => $status,
                    ]);

                }

                return;
            }

            $paymentProcess->model?->transaction?->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

        } catch (Throwable $e) {
            Log::error($e->getMessage(), [
                $e->getFile(),
                $e->getLine(),
                $e->getTrace()
            ]);
        }
    }

    /**
     * @param array $data
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function getPayload(array $data, array $payload): array
    {
        $key    = '';
        $before = [];

        if (data_get($data, 'ads_package_id')) {

            $key = 'ads_package_id';
            $before = $this->beforePackage($data, $payload);

        } else if (data_get($data, 'wallet_id')) {

            $key = 'wallet_id';
            $before = $this->beforeWallet($data, $payload);

        }

        return [
            $key,
            $before
        ];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforePackage(array $data, array|null $payload): array
    {
        $adsPackage = AdsPackage::find(data_get($data, 'ads_package_id'));
        $totalPrice = round($adsPackage->price * 100, 1);

        $currency = Currency::find($this->currency);

        return [
            'model_type'  => AdsPackage::class,
            'model_id'    => $adsPackage->id,
            'total_price' => $totalPrice,
            'currency'    => $currency?->title ?? data_get($payload, 'currency')
        ];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforeWallet(array $data, array|null $payload): array
    {
        $model = Wallet::find(data_get($data, 'wallet_id'));

        $totalPrice = round((double)data_get($data, 'total_price') * 100, 1);

        $currency = Currency::find($this->currency);

        return [
            'model_type'  => get_class($model),
            'model_id'    => $model->id,
            'total_price' => $totalPrice,
            'currency'    => $currency?->title ?? data_get($payload, 'currency')
        ];
    }

}
