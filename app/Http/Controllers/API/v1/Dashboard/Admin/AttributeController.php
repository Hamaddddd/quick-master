<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Attribute\StoreManyRequest;
use App\Http\Requests\Attribute\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Attribute\StoreRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Repositories\AttributeRepository\AttributeRepository;
use App\Services\AttributeService\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttributeController extends AdminBaseController
{
    public function __construct(
        private AttributeRepository $repository,
        private AttributeService $service
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->index($request->all());

        return AttributeResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Attribute $attribute
     * @return JsonResponse
     */
    public function show(Attribute $attribute): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            AttributeResource::make($this->repository->show($attribute))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            AttributeResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreManyRequest $request
     * @return JsonResponse
     */
    public function storeMany(StoreManyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->createMany($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            AttributeResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function update(Attribute $attribute, UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($attribute, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            AttributeResource::make(data_get($result, 'data'))
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
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
