<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Report\StatRequest;
use App\Http\Requests\Report\MainRequest;
use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends UserBaseController
{

    public function __construct(private DashboardRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param StatRequest $request
     * @return JsonResponse
     */
    public function statistics(StatRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->repository->statistics($validated);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param MainRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsStatistic(MainRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $products = $this->repository->productsStatistic($validated);

        return ProductResource::collection($products);
    }

}
