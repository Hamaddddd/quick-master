<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Product;
use App\Repositories\FilterRepository\FilterRepository;

class FilterController extends RestBaseController
{
    public function __construct(private FilterRepository $repository)
    {
        parent::__construct();
    }

    public function filter(FilterRequest $request): array
    {
        $validated = $request->all();
        $validated['status'] = Product::PUBLISHED;

        return $this->repository->filter($validated);
    }

    public function search(SearchRequest $request): array
    {
        return $this->repository->search($request->validated());
    }
}
