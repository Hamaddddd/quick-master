<?php
declare(strict_types=1);

namespace App\Http\Requests\Attribute;

use App\Http\Requests\BaseRequest;
use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Validation\Rule;

class StoreManyRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'data'                     => 'required|array',
            'data.*'                   => 'required|array',
            'data.*.category_id'       => [
                'required',
                Rule::exists('categories','id')->whereNot('type', Category::CAREER),
            ],
            'data.*.required'          => ['required', 'bool'],
            'data.*.type'              => ['required', 'string', Rule::in(Attribute::TYPES)],
            'data.*.delete_value_ids'  => 'array',
            'data.*.delete_value_ids.*'=> 'int',
            'data.*.value'             => 'array',
            'data.*.value.*'           => 'required|array',
            'data.*.value.*.*'         => 'required|string',
            'data.*.title'             => 'required|array',
            'data.*.title.*'           => 'required|string|max:191',
            'data.*.placeholder'       => 'array',
            'data.*.placeholder.*'     => 'required|string|max:191',
            'data.*.placeholder_to'    => 'array',
            'data.*.placeholder_to.*'  => 'string|max:191',
        ];
    }
}
