<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;

class ProductAdsPackageStoreRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_ads_package_id' => 'required|exists:user_ads_packages,id',
            'product_id'          => 'required|exists:products,id',
        ];
    }
}
