<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use Artisan;
use DateTimeZone;
use App\Helpers\ResponseError;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Report\StatRequest;
use App\Http\Requests\Report\MainRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends AdminBaseController
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
        $result = $this->repository->statistics($request->validated());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param MainRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsStatistic(MainRequest $request): AnonymousResourceCollection
    {
        $products = $this->repository->productsStatistic($request->validated());

        return ProductResource::collection($products);
    }

    /**
     * @param MainRequest $request
     * @return Paginator
     */
    public function usersStatistic(MainRequest $request): Paginator
    {
        return $this->repository->usersStatistic($request->validated());
    }

    /**
     * @return JsonResponse
     */
    public function timeZones(): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DateTimeZone::listIdentifiers()
        );
    }

    /**
     * @return JsonResponse
     */
    public function timeZone(): JsonResponse
    {
        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'timeZone'  => config('app.timezone'),
            'time'      => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function timeZoneChange(FilterParamsRequest $request): JsonResponse
    {
        $timeZone = $request->input('timezone');
        $oldZone  = config('app.timezone');

        if (empty($timeZone) || !in_array($timeZone, DateTimeZone::listIdentifiers())) {
            $timeZone = $oldZone;
        }

        $path = base_path('config/app.php');

        file_put_contents(
            $path, str_replace(
                "'timezone' => '$oldZone',",
                "'timezone' => '$timeZone',",
                file_get_contents($path)
            )
        );

        Artisan::call('cache:clear');

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $timeZone);
    }
}
