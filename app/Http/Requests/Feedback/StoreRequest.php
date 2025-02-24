<?php
declare(strict_types=1);

namespace App\Http\Requests\Feedback;

use App\Http\Requests\BaseRequest;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'helpful'    => 'boolean'
        ];
    }
}
