<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id'                       => 'integer|exists:users,id',
            'currency_id'                   => 'required|integer|exists:currencies,id',
            'payment_id'                    => [
                'integer',
                Rule::exists('payments', 'id')->whereIn('tag', ['wallet', 'cash'])
            ],
            'rate'                          => 'numeric',
            'location'                      => 'array',
            'location.latitude'             => 'numeric',
            'location.longitude'            => 'numeric',
            'address'                       => 'array',
            'phone'                         => 'string',
            'username'                      => 'string',
            'products'                      => 'array',
            'products.*.product_id'  => [
                'int',
                Rule::exists('products', 'id')
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
            ],
            'products.*.quantity'  => ['int'],
        ];
    }
}
