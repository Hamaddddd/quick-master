<?php
declare(strict_types=1);

namespace App\Http\Requests\WorkRequest;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

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
            'product_id'        => [
                'required',
                Rule::exists('products', 'id')->where('work', true)
            ],
            'save_resume'       => 'boolean',
            'first_name'        => 'string',
            'last_name'         => 'string',
            'phone'             => 'string',
            'email'             => 'email|string',
            'resume'            => 'string',
            'files'             => 'array|max:5',
            'files.*'           => 'string',
            'message'           => 'string',
        ];
    }
}
