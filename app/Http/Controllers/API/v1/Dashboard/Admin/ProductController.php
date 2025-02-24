<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ProductExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Product\AdminRequest;
use App\Http\Requests\Product\AdminUpdateRequest;
use App\Http\Requests\Product\StatusRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserActivityResource;
use App\Imports\ProductImport;
use App\Models\Category;
use App\Repositories\ProductRepository\ProductReportRepository;
use App\Repositories\ProductRepository\ProductRepository;
use App\Services\ProductService\ProductService;
use App\Traits\Loggable;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductController extends AdminBaseController
{
    use Loggable;

    public function __construct(
        private ProductService          $service,
        private ProductRepository       $repository,
        private ProductReportRepository $reportRepository,
    )
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->repository->productsPaginate($request->all());

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        if (empty($product)) {
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
     * @param AdminUpdateRequest $request
     * @param string $slug
     * @return JsonResponse
     */
    public function update(AdminUpdateRequest $request, string $slug): JsonResponse
    {
        $validated = $request->validated();

        $category = Category::select(['work'])->find($validated['category_id']);

        $validated['work'] = !empty($category) ? $category->work : false;

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
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
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
        $result = $this->service->setActive($slug);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make($result['data'])
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $slug
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function setStatus(string $slug, StatusRequest $request): JsonResponse
    {
        $result = $this->service->setStatus($slug, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/products.xlsx';

        $productExport = new ProductExport($request->merge(['language' => $this->language])->all());

        try {
            Excel::store($productExport, $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);

            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
                'path'      => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->errorResponse(ResponseError::ERROR_400, $e->getMessage());
        }
    }

    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {
            Excel::import(new ProductImport($this->language), $request->file('file'));
            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
        } catch (Exception $e) {
            return $this->onErrorResponse([
                'code'  => ResponseError::ERROR_508,
                'data'  => $e->getMessage()
            ]);
        }
    }

    public function reportPaginate(FilterParamsRequest $request): Paginator|array
    {
        try {
            return $this->reportRepository->productReportPaginate($request->all());
        } catch (Exception $exception) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $exception->getMessage()
            ];
        }
    }

    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $history = $this->reportRepository->history($request->all());

        return ProductResource::collection($history);
    }

    public function mostPopulars(FilterParamsRequest $request): AnonymousResourceCollection
    {
        return UserActivityResource::collection($this->reportRepository->mostPopulars($request->all()));
    }
}
