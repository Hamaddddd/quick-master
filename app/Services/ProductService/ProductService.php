<?php
declare(strict_types=1);

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Settings;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class ProductService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory((int)data_get($data, 'category_id'))
            ) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => __('errors.'. ResponseError::CATEGORY_IS_PARENT, locale: $this->language)
                ];
            }

            $autoApprove = Settings::where('key', 'product_auto_approve')->first()?->value;

            if ($autoApprove) {
                $data['status'] = Product::PUBLISHED;
                $data['active'] = true;
            }

            $data['work'] = Category::select(['work'])->find($data['category_id'])?->work ?? false;

            /** @var Product $product */
            $product = $this->model()->create($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                $product->uploads(data_get($data, 'images'));
            }

            $this->updateProductAttributeValues($product, $data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $product->loadMissing(['translations', 'metaTags'])
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $slug
     * @param array $data
     * @return array
     */
    public function update(string $slug, array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory((int)data_get($data, 'category_id'))
            ) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => __('errors.'. ResponseError::CATEGORY_IS_PARENT, locale: $this->language)
                ];
            }

            $product = $this->model()
                ->when(request()->is('api/v1/dashboard/user/*'), function ($q) {
                    $q->where('user_id', auth('sanctum')->id());
                })
                ->firstWhere('slug', $slug);

            if (empty($product)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            $data['status_note'] = null;
            $data['work'] = Category::select(['work'])->find($data['category_id'])?->work ?? false;

            /** @var Product $product */
            $product->update($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->galleries()->delete();
                $product->update([ 'img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                $product->uploads(data_get($data, 'images'));
            }

            $this->updateProductAttributeValues($product, $data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $product->loadMissing(['translations', 'metaTags'])
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $userId
     * @return array
     */
    public function delete(?array $ids = [], ?int $userId = null): array
    {
        $products = Product::whereIn('id', $ids)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get();

        $errorIds = [];

        foreach ($products as $product) {
            try {
                /** @var Product $product */
                $product->delete();
            } catch (Throwable) {
                $errorIds[] = $product->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return ['status' => false, 'code' => ResponseError::ERROR_505, 'message' => implode(', ', $errorIds)];
    }

    /**
     * @param int|string $categoryId
     * @return bool
     */
    private function checkIsParentCategory(int|string $categoryId): bool
    {
        $parentCategory = Category::firstWhere('parent_id', $categoryId);

        return !!data_get($parentCategory, 'id');
    }

    public function setActive(string $slug, ?int $userId = null): array
    {
        $product = Product::when($userId, fn($q) => $q->where('user_id', $userId))->firstWhere('slug', $slug);

        if (empty($product)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ];
        }

        $product->update(['active' => !$product->active]);

        return [
            'status' => true,
            'data'   => $product
        ];
    }

    /**
     * @param string $slug
     * @param array $data
     * @return array
     */
    public function setStatus(string $slug, array $data): array
    {
        /** @var Product $product */
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $actual = $product->id;

        if (!$actual) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_430,
                'message' => __('errors.' . ResponseError::ERROR_430, locale: $this->language)
            ];
        }

        $product->update([
            'status'      => data_get($data, 'status'),
            'status_note' => data_get($data, 'status_note', '')
        ]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $product
        ];

    }

    /**
     * @param Product $product
     * @param $data
     * @return void
     */
    public function updateProductAttributeValues(Product $product, $data): void
    {

        if (!isset($data['attribute_values'])) {
            return;
        }

        $values = collect($data['attribute_values']);


        $product->attributeValueProducts()->delete();

        foreach ($values as $value) {

            $product->attributeValueProducts()->create([
                'attribute_id'       => $value['attribute_id'],
                'attribute_value_id' => $value['value_id'] ?? null,
                'value'              => $value['value'] ?? null,
            ]);

        }

    }
}
