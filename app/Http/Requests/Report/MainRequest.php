<?php
declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;

class MainRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date_from'  => 'required|string|date_format:Y-m-d',
            'date_to'    => 'required|string|date_format:Y-m-d',
            'perPage'    => 'int|max:100',
            'user_id'    => 'int|exists:users,id',
            'product_id' => 'int|exists:products,id'
        ];
    }
}
