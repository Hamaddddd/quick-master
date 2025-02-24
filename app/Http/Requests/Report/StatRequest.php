<?php
declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StatRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'time' => ['required', 'string', Rule::in(User::DATES)],
        ] + (new MainRequest)->rules();
    }
}
