<?php
declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\Order\StoreRequest;
use Illuminate\Validation\Rule;
use Log;
use ReflectionClass;

class PaymentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId         = auth('sanctum')->id();

        $orderId        = request('order_id');
        $adsPackageId   = request('ads_package_id');
        $walletId       = request('wallet_id');

        $rules = [];

        if ($orderId) {
            $rules = (new StoreRequest)->rules();
        }

        $reflectionClass = new ReflectionClass('Iyzipay\Model\PaymentChannel');
        $constants = $reflectionClass->getConstants();

        return [
            'order_id' => [
                !$adsPackageId && !$walletId ? 'required' : 'nullable',
                Rule::exists('orders', 'id')->where('user_id', $userId)
            ],
            'ads_package_id' => [
                !$orderId && !$walletId ? 'required' : 'nullable',
                Rule::exists('ads_packages', 'id')
                    ->where('active', true)
            ],
            'wallet_id' => [
                !$orderId && !$adsPackageId ? 'required' : 'nullable',
                Rule::exists('wallets', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'total_price' => [
                !$orderId && !$adsPackageId ? 'required' : 'nullable',
                'numeric'
            ],
            'holder_name'  => 'string|min:5|max:255',
            'card_number'  => 'numeric',
            'expire_month' => 'numeric|max:12',
            'expire_year'  => 'int',
            'cvc' 		   => 'string|max:255',
            'chanel' 	   => 'string|in:' . implode(',', $constants),
        ] + $rules;
    }

}
