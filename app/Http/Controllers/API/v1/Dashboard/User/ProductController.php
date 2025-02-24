<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository\ProductRepository;
use App\Services\ProductService\ProductReviewService;
use App\Services\ProductService\ProductService;
use App\Traits\Loggable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends UserBaseController
{
    use Loggable;

    public function __construct(
        private ProductService       $service,
        private ProductRepository    $repository,
        private ProductReviewService $reviewService
    )
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $products = $this->repository->productsPaginate($filter);

        return ProductResource::collection($products);
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
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->repository->productBySlug($slug);

        if (empty($product) || $product->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProductResource::make($product->loadMissing(['translations', 'metaTags']))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param string $slug
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $slug): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($slug, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
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
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $categories = $this->repository->productsSearch($request->merge(['visibility' => true])->all());

        return ProductResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function setActive(string $slug): JsonResponse
    {
        $result = $this->service->setActive($slug, auth('sanctum')->id());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make($result['data'])
        );
    }

    /**
     * Add review to product
     *
     * @param string $slug
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addProductReview(string $slug, AddReviewRequest $request): JsonResponse
    {
        $result = $this->reviewService->addReview($slug, $request);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

}
