<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\AttributeResource;
use App\Repositories\AttributeRepository\AttributeRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttributeController extends Controller
{
    use ApiResponse;

    public function __construct(private AttributeRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $attributes = $this->repository->paginate($request->all());

        return AttributeResource::collection($attributes);
    }

    /**
     * @param int $categoryId
     * @return AnonymousResourceCollection
     */
    public function show(int $categoryId): AnonymousResourceCollection
    {
        $attributes = $this->repository->showByCategoryId($categoryId);

        return AttributeResource::collection($attributes);
    }
}
