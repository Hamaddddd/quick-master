<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;

class UserAdsPackageStoreRequest extends BaseRequest
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
            'ads_package_id' => 'required|exists:ads_packages,id',
        ];
    }
}
