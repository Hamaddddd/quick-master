<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\WorkRequest\StoreRequest;
use App\Http\Resources\WorkRequestResource;
use App\Models\WorkRequest;
use App\Repositories\WorkRequestRepository\WorkRequestRepository;
use App\Services\WorkRequestService\WorkRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkRequestController extends UserBaseController
{

    public function __construct(private WorkRequestRepository $repository, private WorkRequestService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return WorkRequestResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
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
            WorkRequestResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param WorkRequest $workRequest
     * @return JsonResponse
     */
    public function show(WorkRequest $workRequest): JsonResponse
    {
        $model = $this->repository->show($workRequest);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            WorkRequestResource::make($model)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param WorkRequest $workRequest
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(WorkRequest $workRequest, StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->update($workRequest, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            WorkRequestResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []), auth('sanctum')->id());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productWorkRequestIndex(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->merge(['owner_id' => auth('sanctum')->id()])->all());

        return WorkRequestResource::collection($models);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function workRequestShow(int $id): JsonResponse
    {
        $model = $this->repository->showById($id, auth('sanctum')->id());

        if (empty($model)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            WorkRequestResource::make($model)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productWorkRequestShow(int $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request
            ->merge(['owner_id' => auth('sanctum')->id(), 'product_id' => $id])
            ->all();

        $models = $this->repository->paginate($filter);

        return WorkRequestResource::collection($models);
    }
}
