<?php
declare(strict_types=1);

namespace App\Http\Requests\Attribute;

use App\Http\Requests\BaseRequest;
use App\Models\Attribute;
use App\Models\Category;
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
            'category_id' => [
                Rule::exists('categories','id')->whereNot('type', Category::CAREER),
            ],
            'required'          => ['required', 'bool'],
            'type'              => ['string', Rule::in(Attribute::TYPES)],
            'delete_value_ids'  => 'array',
            'delete_value_ids.*'=> 'int',
            'value'             => 'array',
            'value.*'           => 'array',
            'value.*.*'         => 'string',
            'title'             => 'array',
            'title.*'           => 'string|max:191',
            'placeholder'       => 'array',
            'placeholder.*'     => 'string|max:191',
            'placeholder_to'    => 'array',
            'placeholder_to.*'  => 'string|max:191',
        ];
    }
}
