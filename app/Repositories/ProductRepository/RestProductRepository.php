<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

use Schema;
use Closure;
use App\Models\Product;
use App\Models\Category;
use App\Helpers\Utility;
use App\Traits\ByLocation;
use App\Jobs\UserActivityJob;
use Illuminate\Support\Facades\DB;
use App\Repositories\CoreRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RestProductRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @return Closure[]
     */
    public function with(): array
    {
        return [
            'translation' => fn($query) => $query->where('locale', $this->language),
        ] + $this->getWith();
    }

    public function productWith(): array
    {
        return [
            'translation'         => fn($query) => $query->where('locale', $this->language),
            'region.translation'  => fn($query) => $query->where('locale', $this->language),
            'country.translation' => fn($query) => $query->where('locale', $this->language),
            'city.translation'    => fn($query) => $query->where('locale', $this->language),
            'area.translation'    => fn($query) => $query->where('locale', $this->language),
        ];
    }

    /**
     * @return Closure[]
     */
    public function showWith(): array
    {
        return [
            'brand',
            'category'             => fn($q) => $q->select('id', 'uuid'),
            'category.translation' => fn($q) => $q->where('locale', $this->language)->select('id', 'category_id', 'locale', 'title'),
            'unit.translation'     => fn($q) => $q->where('locale', $this->language),
            'translation'          => fn($q) => $q->where('locale', $this->language),
            'galleries'            => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
            'attributeValueProducts.attribute.translation'      => fn($q) => $q->where('locale', $this->language),
            'attributeValueProducts.attributeValue.translation' => fn($q) => $q->where('locale', $this->language),
        ] + $this->getWith();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->filter($filter)
            ->actual($this->language)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
            ] + $this->getWith())
            ->whereHas('translation', fn($query) => $query->where('locale', $this->language))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function productsWithCategories(array $filter): array
    {
        /** @var Category $category */
        $category    = Category::with(['children:id,parent_id'])->find(data_get($filter, 'category_id'));
        $categoryIds = $category?->children?->pluck('id')?->merge([$category?->id])?->toArray();

        $categories = Category::where('active', true)
            ->when($categoryIds,
                fn($q)      => $q->whereIn('id', $categoryIds)->when($category->type === Category::MAIN, fn($q) => $q->where('type', Category::MAIN)),
                fn($q, $id) => $q->where('type', Category::MAIN),
            )
            ->with([
                'translation'          => fn($q) => $q->where('locale', $this->language)->select('id', 'locale', 'title', 'category_id'),
                'children.translation' => fn($q) => $q->where('locale', $this->language)->select('id', 'locale', 'title', 'category_id'),
                'products' => fn($q) => $q
                    ->with($this->productWith())
                    ->filter($filter)
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
                    ->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
                    ->whereHas('translation', fn($query) => $query->where('locale', $this->language))
                    ->select([
                        'id',
                        'slug',
                        'brand_id',
                        'category_id',
                        'region_id',
                        'country_id',
                        'city_id',
                        'area_id',
                        'img',
                        'status',
                        'active',
                        'price',
                        'created_at',
                        'updated_at',
                    ]),
                'children.products' => fn($q) => $q
                    ->with($this->productWith())
                    ->filter($filter)
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
                    ->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
                    ->whereHas('translation', fn($query) => $query->where('locale', $this->language))
                    ->select([
                        'id',
                        'slug',
                        'brand_id',
                        'category_id',
                        'region_id',
                        'country_id',
                        'city_id',
                        'area_id',
                        'img',
                        'status',
                        'active',
                        'price',
                        'created_at',
                        'updated_at'
                    ]),

            ])
            ->when(data_get($filter, 'parent_id'), fn($q, $parentId) => $q->where('parent_id', $parentId))
            ->paginate(data_get($filter, 'perPage', 10));

        return [
            'all' => CategoryResource::collection($categories),
        ];
    }


    /**
     * @param string $slug
     * @return Product|null
     */
    public function productBySlug(string $slug): ?Product
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->whereHas('translation', fn($query) => $query->where('locale', $this->language))
            ->with(
                $this->showWith() + [
                    'user' => fn($q) => $q->select('id', 'uuid', 'firstname', 'lastname', 'img', 'gender', 'created_at')
                ]
            )
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('slug', $slug)
            ->first()
            ->setAttribute('get_attributes', true);
    }

    /**
     * @param string $slug
     * @return Product|null
     */
    public function showPhone(string $slug): ?Product
    {
        return $this->model()
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('slug', $slug)
            ->select('id', 'phone', 'phone_views_count')
            ->first();
    }

    /**
     * @param string $slug
     * @return Product|null
     */
    public function showMessageClickCounts(string $slug): ?Product
    {
        return $this->model()
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('slug', $slug)
            ->select('id', 'message_click_count')
            ->first();
    }

    /**
     * @param array $filter
     * @return Model|\Illuminate\Database\Eloquent\Builder|Product|Collection|null
     */
    public function productsByIDs(array $filter = []): Model|Builder|Product|Collection|null
    {
        $ids = data_get($filter, 'products', []);

        $toStringIds = implode(', ' , $ids);

        return $this->model()
            ->filter($filter)
            ->actual($this->language)
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->whereIn('id', $ids)
            ->orderByRaw(DB::raw("FIELD(id, $toStringIds)"))
            ->get();
    }

    /**
     * @param array $filter
     * @return Builder|Collection|null
     */
    public function compare(array $filter = []): Collection|Builder|null
    {
        $products = Product::actual($this->language)
            ->with($this->showWith())
            ->find($filter['ids']);

        return $products->groupBy('category_id')->values();
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'reviewable_type' => Product::class,
            'reviewable_id'   => $id,
        ]);
    }

    /**
     * @param string $slug
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function related(string $slug, array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();
        $column  = $filter['column'] ?? 'id';
        $sort    = $filter['sort'] ?? 'desc';

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        $related = Product::firstWhere('slug', $slug);

        if ($related?->id) {
            UserActivityJob::dispatchAfterResponse(
                $related->id,
                get_class($related),
                'click',
                1,
                auth('sanctum')->user()
            );
        }

        $categoryId = $related?->category_id;
        $branId     = $related?->brand_id;

        return $product
            ->actual($this->language)
            ->with([
                'unit.translation' => fn($q) => $q->where('locale', $this->language),
                'translation'      => fn($q) => $q->where('locale', $this->language),
            ] + $this->getWith())
            ->where('id', '!=', $related?->id)
            ->where(function ($query) use ($branId, $categoryId) {
                $query
                    ->where('category_id', $categoryId)
                    ->when($branId, fn($q, $id) => $q->orWhere('brand_id', $id));
            })
            ->when(
                $column === 'category_id' && $categoryId,
                fn($q) => $q->orderByRaw(DB::raw("FIELD(id, $categoryId) $sort")),
                fn($q) => $q->orderBy($column, $sort),
            )
            ->paginate($filter['perPage'] ?? 10);
    }
}
