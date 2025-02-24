<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Addons\AdsPackage\Models\UserAdsPackage;
use App\Addons\AdsPackage\Resources\UserAdsPackageResource;
use App\Helpers\ResponseError;
use App\Http\Requests\Payment\TransactionRequest;
use App\Http\Requests\Payment\TransactionUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WalletResource;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;

class TransactionController extends PaymentBaseController
{

    public function __construct(private TransactionService $service)
    {
        parent::__construct($service);
    }

    public function store(string $type, int $id, TransactionRequest $request): JsonResponse
    {
        if ($type === 'order') {

            $result = $this->service->orderTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                OrderResource::make(data_get($result, 'data'))
            );

        } else if ($type === 'ads') {
            $result = $this->service->adsTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                UserAdsPackageResource::make(data_get($result, 'data'))
            );
        }

        $result = $this->service->walletTransaction($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            WalletResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @throws Exception
     */
    public function updateStatus(string $type, int $id, TransactionUpdateRequest $request): JsonResponse
    {
        if (!auth('sanctum')->user()?->hasRole(['admin', 'seller'])) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $model = match($type) {
            'ads-package', 'ads' => UserAdsPackage::with('transaction')->find($id),
            'wallet'             => Wallet::with(['transaction' => fn($q) => $q->orderBy('id', 'desc')])->find($id),
            default              => throw new Exception('undefined type'),
        };

        if (!$model) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var UserAdsPackage $model */
        if (!$model->transaction) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ]);
        }

        /** @var Transaction $transaction */
        $model->transaction->update([
            'status' => $request->input('status')
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $model->fresh('transaction')
        );
    }
}
