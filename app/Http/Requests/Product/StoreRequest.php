<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use App\Models\Product;
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
            'category_id'           => [
                'required',
                Rule::exists('categories', 'id')->where('active', true),
            ],
            'brand_id'              => ['nullable', Rule::exists('brands', 'id')],
            'unit_id'               => [
                'nullable',
                Rule::exists('units', 'id')->where('active', true)
            ],
            'parent_id'             => [
                'nullable',
                Rule::exists('products', 'id')->whereNull('parent_id')
            ],
            'keywords'              => 'string',
            'images'                => 'array',
            'images.*'              => 'string',
            'previews'              => 'array',
            'previews.*'            => 'string',
            'title'                 => ['required', 'array'],
            'title.*'               => ['required', 'string', 'min:1', 'max:191'],
            'description'           => 'array',
            'description.*'         => 'string|min:1',
            'region_id'             => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
            ],
            'country_id'            => [
                'required',
                'integer',
                Rule::exists('countries', 'id')->where('region_id', request('region_id'))
            ],
            'city_id'               => [
                'integer',
                Rule::exists('cities', 'id')->where('country_id', request('country_id'))
            ],
            'area_id'               => [
                'integer',
                Rule::exists('areas', 'id')->where('city_id', request('city_id'))
            ],
            'type'                  => ['required', 'string', Rule::in(Product::TYPES)],
            'active'                => 'boolean',
            'state'                 => ['required', Rule::in(Product::STATES)],
            'contact_name'          => 'string',
            'email'                 => 'string|email',
            'price'                 => 'numeric',
            'price_from'            => 'numeric',
            'price_to'              => 'numeric',
            'phone'                 => 'required|string',
            'meta'                  => 'array',
            'meta.*'                => 'array',
            'meta.*.path'           => 'string',
            'meta.*.title'          => 'required|string',
            'meta.*.keywords'       => 'string',
            'meta.*.description'    => 'string',
            'meta.*.h1'             => 'string',
            'meta.*.seo_text'       => 'string',
            'meta.*.canonical'      => 'string',
            'meta.*.robots'         => 'string',
            'meta.*.change_freq'    => 'string',
            'meta.*.priority'       => 'string',

            'attribute_values'            => 'array',
            'attribute_values.*.value'    => 'string',
            'attribute_values.*.attribute_id' => [
                Rule::exists('attributes', 'id')
            ],
            'attribute_values.*.value_id' => [
                Rule::exists('attribute_values', 'id')
            ],
        ];
    }
}
