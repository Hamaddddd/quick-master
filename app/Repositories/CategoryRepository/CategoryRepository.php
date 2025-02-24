<?php
declare(strict_types=1);

namespace App\Repositories\CategoryRepository;

ini_set('memory_limit', '4G');
set_time_limit(0);

use App\Exports\CategoryReportExport;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CategoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function categories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        $locale = Language::where('default', 1)->first()?->locale;

        return $category
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function parentCategories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->filter($filter)
            ->withThreeChildren($filter + ['lang' => $this->language])
            ->with([
                'translations',
            ])
            ->where(fn($q) => $q->where('parent_id', null)->orWhere('parent_id', 0))
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @return Category|null
     */
    public function childrenCategory(int $id): ?Category
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->withSecondChildren(['lang' => $this->language])
            ->find($id);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function selectPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->filter($filter)
            ->withThreeChildren($filter)
            ->select(['id', 'parent_id', 'keywords', 'type', 'active', 'input', 'age_limit'])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one category by slug
     * @param $uuid
     * @return Category|null
     */
    public function categoryByUuid($uuid): ?Category
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->where('uuid', $uuid)
            ->withThreeChildren(['lang' => $this->language])
            ->withCount(['products'])
            ->first();
    }

    /**
     * Get one category by slug
     * @param string $slug
     * @return Category|null
     */
    public function categoryBySlug(string $slug): ?Category
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->where('slug', $slug)
            ->withThreeChildren(['lang' => $this->language])
            ->first();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function categoriesSearch(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $category
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language)
            ])
            ->latest()
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return array
     */
    public function reportChart(array $filter = []): array
    {
        $dateFrom  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo    = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
        $type      = data_get($filter, 'type');
        $search    = data_get($filter, 'search');
        $chartType = data_get($filter, 'chart', 'count');

        $categories = Category::with([
            'parent:id',
            'parent.translation' => fn($q) => $q
                ->where('locale', $this->language)
                ->select('id', 'locale', 'title', 'category_id'),

            'translation' => fn($q) => $q
                ->where('locale', $this->language)
                ->select('id', 'locale', 'title', 'category_id'),

            'products' => fn($q) => $q->select('id', 'category_id', 'created_at'),
        ])
            ->where(function ($q) use ($search) {
                $q->whereHas('translation', fn($query) => $query
                    ->where('locale', $this->language)
                    ->where('title', 'like', "%$search%")
                );
            })
            ->whereHas('products', function ($query) use ($dateFrom, $dateTo) {
                $query
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            })
            ->select([
                'id',
                'created_at'
            ])
            ->get();

        $paginate = [];

        foreach ($categories as $category) {

            /** @var Category $category */
            $key = $category->id;

            if (!data_get($paginate, $key)) {

                $paginate[$key] = [
                    'id'             => $category->id,
                    'created_at'     => $category->created_at?->format('Y-m-d'),
                    'title'          => $category->translation?->title,
                    'price'          => 0,
                    'products_count' => 0,
                ];

            }

            $this->reportPaginateData($category, $key, $paginate);

        }

        $chart = [];

        $paginate = collect($paginate);

        foreach ($paginate as $item) {

            $time = data_get($item, 'created_at');

            if ($type === 'year') {
                $time = $item->created_at?->format('Y');
            } else if ($time === 'month') {
                $time = $item->created_at?->format('Y-m');
            }

            if (empty($time)) {
                continue;
            }

            if (!data_get($chart, $time)) {

                $chart[$time] = [
                    'time'      => $time,
                    $chartType  => data_get($item, $chartType)
                ];

                continue;
            }

            $value = data_get($chart, "$time.$chartType") + data_get($item, $chartType);

            $chart[$time] = [
                'time'      => $time,
                $chartType  => $value
            ];

        }

        if (data_get($filter, 'export') === 'excel') {
            try {
                $name = 'categories-report-' . Str::random(8);

                Excel::store(new CategoryReportExport($paginate), "export/$name.xlsx",'public', \Maatwebsite\Excel\Excel::XLSX);

                return [
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status' => false,
                    'message' => 'Cant export category. ' . $e->getMessage()
                ];
            }
        }

        return [
            'paginate'              => $paginate->values()->toArray(),
            'chart'                 => array_values($chart),
            'currency'              => Currency::currenciesList()->where('id', $this->currency)->first(),
            'total_price'           => $paginate->sum('price'),
            'total_products_count'  => $paginate->sum('products_count'),
        ];
    }

    /**
     * @param Category|null $category
     * @param $key
     * @param $paginate
     * @return void
     */
    private function reportPaginateData(?Category $category, $key, &$paginate): void
    {
        if (empty($category)) {
            return;
        }

        $title = data_get($paginate, "$key.title", '');

        $parentTitle = $category->parent?->translation?->title;

        if (!empty($parentTitle)) {
            data_set($paginate, "$key.title", "$parentTitle -> $title");
        }

        $count = data_get($paginate, "$key.products_count", 0);

        $products = $category->products;

        data_set($paginate, "$key.products_count", $count + $products->count());

        foreach ($products as $product) {

            /** @var Product $product */

            $price    = data_get($paginate, "$key.price", 0);

            $sumPrice = $product->sum('total_price');

            data_set($paginate, "$key.price", $price + $sumPrice);

        }

    }
}
