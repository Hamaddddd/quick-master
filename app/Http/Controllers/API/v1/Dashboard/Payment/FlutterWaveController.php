<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use Illuminate\Http\Request;
use App\Models\WalletHistory;
use App\Services\PaymentService\FlutterWaveService;

class FlutterWaveController extends PaymentBaseController
{
    public function __construct(private FlutterWaveService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        $status = $request->input('status');

        $status = match ($status) {
            'successful' => WalletHistory::PAID,
            default      => 'progress',
        };

        $token = $request->input('txRef');

        $this->service->afterHook($token, $status);
    }

}
