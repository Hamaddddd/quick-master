<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Feedback\StoreRequest;
use App\Services\FeedbackService\FeedbackService;

class FeedbackController extends UserBaseController
{

    public function __construct(private FeedbackService $service)
    {
        parent::__construct();
    }

    /**
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
        );

    }

}
