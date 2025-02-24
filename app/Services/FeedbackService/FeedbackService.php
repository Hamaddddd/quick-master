<?php
declare(strict_types=1);

namespace App\Services\FeedbackService;

use App\Helpers\ResponseError;
use App\Models\Feedback;
use App\Models\Product;
use App\Services\CoreService;
use Throwable;

class FeedbackService extends CoreService
{

    protected function getModelClass(): string
    {
        return Feedback::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            $product = Product::where('active', false)->find($data['product_id']);

            if (empty($product->user_id) || $product->user_id !== $data['user_id']) {
                return [
                    'status' => false,
                    'code'   => ResponseError::WRONG_PRODUCT
                ];
            }

            Feedback::where([
                'user_id'    => $data['user_id'],
                'product_id' => $product->id
            ])->delete();

            $this->model()->create($data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
