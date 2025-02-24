<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Services\CoreService;

class OrderReviewService extends CoreService
{

    protected function getModelClass(): string
    {
        return Order::class;
    }

    public function addReview(Order $order, $collection): array
    {
        $order->addAssignReview($collection, $order->seller);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $order->load(['reviews.assignable'])
        ];
    }

}
