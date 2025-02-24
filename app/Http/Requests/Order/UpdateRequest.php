<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id'               => 'integer|exists:users,id',
            'currency_id'           => 'integer|exists:currencies,id',
            'rate'                  => 'numeric',
            'location'              => 'array',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'address'               => 'array',
            'phone'                 => 'string',
            'username'              => 'string',
            'images'                => 'array',
            'images.*'              => 'string',
            'product_id'            => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
        ];
    }

}
