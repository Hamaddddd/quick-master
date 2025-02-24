<?php
declare(strict_types=1);

namespace App\Http\Requests\Attribute;

use App\Http\Requests\BaseRequest;
use App\Models\Attribute;
use App\Models\Category;
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
            'category_id'       => [
                'required',
                Rule::exists('categories','id')->whereNot('type', Category::CAREER),
            ],
            'required'          => ['required', 'bool'],
            'type'              => ['required', 'string', Rule::in(Attribute::TYPES)],
            'delete_value_ids'  => 'array',
            'delete_value_ids.*'=> 'int',
            'value'             => 'array',
            'value.*'           => 'required|array',
            'value.*.*'         => 'required|string',
            'title'             => 'required|array',
            'title.*'           => 'required|string|max:191',
            'placeholder'       => 'array',
            'placeholder.*'     => 'required|string|max:191',
            'placeholder_to'    => 'array',
            'placeholder_to.*'  => 'string|max:191',
        ];
    }
}
