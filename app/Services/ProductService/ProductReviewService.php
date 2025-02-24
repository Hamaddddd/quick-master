<?php
declare(strict_types=1);

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\Product;
use App\Services\CoreService;

class ProductReviewService extends CoreService
{

    protected function getModelClass(): string
    {
        return Product::class;
    }

    public function addReview($slug, $collection): array
    {
        /** @var Product $product */

        $product = $this->model()->firstWhere('slug', $slug);

        if (empty(data_get($product, 'id'))) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $product->addAssignReview($collection, $product->user);

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $product
        ];
    }

    /**
     * @param $slug
     * @return array
     */
    public function reviews($slug): array
    {
        /** @var Product $product */
        $product = $this->model()
            ->with([
                'reviews.user:id,firstname,lastname,img,active',
                'reviews.galleries',
            ])
            ->firstWhere('slug', $slug);

        if (empty(data_get($product, 'id'))) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $product->reviews
        ];
    }
}
