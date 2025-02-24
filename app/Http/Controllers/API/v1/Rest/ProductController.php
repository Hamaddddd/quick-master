<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Addons\AdsPackage\Controllers\Rest\AdsProductPaginate;
use App\Helpers\FilePaths;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Jobs\UserActivityJob;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Repositories\ProductRepository\ProductReportRepository;
use App\Repositories\ProductRepository\ProductRepository;
use App\Repositories\ProductRepository\RestProductRepository;
use App\Services\ProductService\ProductReviewService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ProductController extends RestBaseController
{
    public function __construct(
        private ProductRepository $productRepository,
        private RestProductRepository $restProductRepository,
        private ProductReportRepository $productReportRepository,
    )
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
        $products = $this->restProductRepository->productsPaginate(
            $request->merge([
                'status' => Product::PUBLISHED,
            ])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function productsWithCategories(FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $this->restProductRepository->productsWithCategories($request->all())
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function adsPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        if (!FilePaths::adsExists()) {
            return ProductResource::collection([]);
        }

        $products = (new AdsProductPaginate)->adsPaginate(
            $request->merge([
                'rest'   => true,
                'status' => Product::PUBLISHED,
                'active' => 1,
            ])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->restProductRepository->productBySlug($slug);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Product|Builder $product */
        $product->increment('views_count');

        UserActivityJob::dispatchAfterResponse(
            $product->id,
            get_class($product),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProductResource::make($product)
        );
    }

    /**
     * @param string $slug
     * @return JsonResponse
     */
    public function showPhone(string $slug): JsonResponse
    {
        $product = $this->restProductRepository->showPhone($slug);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Product|Builder $product */
        $product->increment('phone_views_count');

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'phone' => $product->phone
            ]
        );

    }

    /**
     * @param string $slug
     * @return JsonResponse
     */
    public function incrementMessageClickCount(string $slug): JsonResponse
    {
        $product = $this->restProductRepository->showMessageClickCounts($slug);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Product|Builder $product */
        $product->increment('message_click_count');

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
        );
    }

    /**
     * Display the specified resource.
     * @param string $slug
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function related(string $slug, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->restProductRepository->related($slug, $request->all());

        return ProductResource::collection($models);
    }

    /**
     * @param int $id
     * @return float[]
     */
    public function reviewsGroupByRating(int $id): array
    {
        return $this->restProductRepository->reviewsGroupByRating($id);
    }

    public function productsByBrand(int $id): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate(
            ['brand_id' => $id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    public function productsByCategoryUuid(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        $category = (new CategoryRepository)->categoryByUuid($uuid);

        if (!$category && data_get($category, 'type') !== Category::MAIN) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $products = $this->restProductRepository->productsPaginate(
            ['category_id' => $category->id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->productsSearch(
            $request->merge(['status' => Product::PUBLISHED, 'active' => 1])->all(),
        );

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function reviews(string $slug): JsonResponse
    {
        $result = (new ProductReviewService)->reviews($slug);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            ReviewResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * Get Products by IDs.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsByIDs(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsByIDs($request->all());

        return ProductResource::collection($products);
    }

    /**
     * Compare products.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection|array
     */
    public function compare(FilterParamsRequest $request): Collection|array
    {
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids) || count($ids) == 0) {
            return [
                'data' => []
            ];
        }

        return [
            'data' => $this->restProductRepository->compare($request->all())
        ];
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter  = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $history = $this->productReportRepository->history($filter);

        return ProductResource::collection($history);
    }

}
