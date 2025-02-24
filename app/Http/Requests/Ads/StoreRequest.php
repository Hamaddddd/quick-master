<?php
declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Addons\AdsPackage\Models\AdsPackage;
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
            'category_id'   => ['required', Rule::exists('categories', 'id')],
            'count'         => 'required|int',
            'active'        => 'required|boolean',
            'time_type'     => ['required', 'string', Rule::in(AdsPackage::TYPES)],
            'time'          => 'required|integer',
            'price'         => 'required|numeric',
            'title'         => 'array',
            'title.*'       => 'string|max:191',
            'description'   => 'array',
            'description.*' => 'string',
            'button_text'   => 'array',
            'button_text.*' => 'string',
        ];
    }
}
