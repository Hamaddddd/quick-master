<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Order\UserStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends UserBaseController
{
    use Notification;

    public function __construct(private OrderRepository $repository, private OrderService $service)
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
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $orders = $this->repository->ordersPaginate($filter);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserStoreRequest $request
     * @return JsonResponse
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        $this->adminNotify($result);
        $this->notify($result);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            OrderResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * @param $result
     * @return void
     */
    public function notify($result): void
    {
        foreach (data_get($result, 'data', []) as $order) {

            /** @var Order $order */
            if (!$order?->seller_id) {
                continue;
            }

            $seller = User::select(['firebase_token', 'id', 'lang'])->find($order?->seller_id);

            $this->sendUsers($order, [$seller]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->repository->orderById($id, userId: auth('sanctum')->id());

        if ($order?->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make($order),
        );
    }

    public function ordersByParentId(int $id): AnonymousResourceCollection
    {
        $orders = $this->repository->ordersByParentId($id, userId: auth('sanctum')->id());

        return OrderResource::collection($orders);
    }

    /**
     * Add Review to Order.
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addOrderReview(int $id, AddReviewRequest $request): JsonResponse
    {
        /** @var Order $order */
        $order = Order::with(['seller:id'])->select(['id', 'user_id', 'seller_id'])->find($id);

        if ($order?->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' =>  __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        $result = (new OrderReviewService)->addReview($order, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderResource::make(data_get($result, 'data'))
        );
    }

}
