<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use App\Models\Product;
use Illuminate\Validation\Rule;

class AdminRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status'  => Rule::in(Product::STATUSES),
            'user_id' => ['required', Rule::exists('users', 'id')],
        ] + (new StoreRequest)->rules();
    }
}
