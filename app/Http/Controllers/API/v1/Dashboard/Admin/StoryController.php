<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Story\StoreRequest;
use App\Http\Requests\Story\UpdateRequest;
use App\Http\Requests\Story\UploadFileRequest;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use App\Repositories\StoryRepository\StoryRepository;
use App\Services\StoryService\StoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoryController extends AdminBaseController
{

    public function __construct(private StoryService $service, private StoryRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $story = $this->repository->index($request->all());

        return StoryResource::collection($story);
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

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language));
    }

    /**
     * Display the specified resource.
     *
     * @param Story $story
     * @return JsonResponse
     */
    public function show(Story $story): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            StoryResource::make($this->repository->show($story)),
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Story $story
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Story $story, UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($story, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UploadFileRequest $request
     * @return JsonResponse
     */
    public function uploadFiles(UploadFileRequest $request): JsonResponse
    {
        $result = $this->service->uploadFiles($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function changeActive(int $id): JsonResponse
    {
        $result = $this->service->changeActive($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
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
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
