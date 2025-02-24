<?php
declare(strict_types=1);

namespace App\Http\Requests\UserResumeRequest;

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
