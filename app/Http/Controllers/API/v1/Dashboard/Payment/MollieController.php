<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Addons\AdsPackage\Models\AdsPackage;
use App\Models\PaymentProcess;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PaymentService\MollieService;
use Exception;
use Illuminate\Http\Request;
use Log;

class MollieController extends PaymentBaseController
{
    public function __construct(private MollieService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     * @throws Exception
     */
    public function paymentWebHook(Request $request): void
    {
        $status = $request->input('status');

        $status = match ($status) {
            'paid'                          => Transaction::STATUS_PAID,
            'canceled', 'expired', 'failed' => Transaction::STATUS_CANCELED,
            default                         => 'progress',
        };

        Log::error('paymentWebHook', $request->all());

        $adsPackageId = (int)$request->input('ads_package_id');
        $walletId     = (int)$request->input('wallet_id');

        if ($adsPackageId) {
            $class  = AdsPackage::class;
            $id     = $adsPackageId;
        } else if ($walletId) {
            $class  = Wallet::class;
            $id     = $walletId;
        } else {
            throw new Exception('undefined type');
        }

        $paymentProcess = PaymentProcess::where([
            'model_type' => $class,
            'model_id'   => $id,
        ])->first();

        $this->service->afterHook($paymentProcess->id, $status);
    }

}
