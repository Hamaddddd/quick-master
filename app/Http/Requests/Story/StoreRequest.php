<?php
declare(strict_types=1);

namespace App\Http\Requests\Story;

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
            'product_id'  => 'exists:products,id',
            'active'      => 'boolean',
            'color'       => 'string|max:10',
            'title'       => 'required|array',
            'title.*'     => 'required|string|max:191',
            'file_urls'   => 'required|array',
            'file_urls.*' => 'required|string',
        ];
    }
}
