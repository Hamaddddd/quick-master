<?php
declare(strict_types=1);

namespace App\Repositories\FilterRepository;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Repositories\CoreRepository;
use App\Traits\SetCurrency;
use Illuminate\Support\Facades\Schema;

class FilterRepository extends CoreRepository
{
    use SetCurrency;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function filter(array $filter): array
    {
        $locale     = Language::where('default', 1)->first()?->locale;
        $lang       = data_get($filter, 'lang', $locale);
        $type       = data_get($filter, 'type');
        $column     = data_get($filter, 'column', 'id');

        $brands     = [];
        $categories = [];
        $attributes = [];

        $min        = 0;
        $max        = 0;

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        if ($type === 'news_letter') {
            $column = 'created_at';
        }

        $products = Product::filter($filter)
            ->actual($this->language)
            ->with([
                'brand:id,title,img,slug',
                'category:id,img,slug',
                'attributeValueProducts.attributeTranslation'      => fn($q) => $q->where('locale', $this->language),
                'attributeValueProducts.attributeValueTranslation' => fn($q) => $q->where('locale', $this->language),
                'category.translation' => fn($q) => $q
                    ->where('locale', $lang)
                    ->select([
                        'id',
                        'category_id',
                        'locale',
                        'title',
                    ]),
            ])
            ->select([
                'id',
                'slug',
                'active',
                'status',
                'category_id',
                'brand_id',
                'age_limit',
                'price',
            ])
            ->when($type !== 'category', function ($query) {
                $query->limit(1000);
            })
            ->when($column, function ($query) use($column, $filter) {
                $query->orderBy($column, data_get($filter, 'sort', 'desc'));
            })
            ->lazy();

        foreach ($products as $product) {

            /** @var Product $product */
            $brand = $product->brand;

            if ($brand?->id && $brand?->title) {
                $brands[$brand->id] = [
                    'id'    => $brand->id,
                    'slug'  => $brand->slug,
                    'img'   => $brand->img,
                    'title' => $brand->title,
                ];
            }

            $category = $product->category;

            if ($category?->id && $category?->translation?->title) {
                $categories[$category->id] = [
                    'id'    => $category->id,
                    'slug'  => $category->slug,
                    'img'   => $category->img,
                    'title' => $category->translation->title
                ];
            }

            $attributeValueProducts = $product->attributeValueProducts;

            foreach ($attributeValueProducts as $attributeValueProduct) {

                if (!$attributeValueProduct?->attributeTranslation?->title) {
                    continue;
                }

                if (!isset($attributes[$attributeValueProduct->attribute_id])) {

                    $attributes[$attributeValueProduct->attribute_id] = [
                        'id'    => $attributeValueProduct->attribute_id,
                        'title' => $attributeValueProduct->attributeTranslation->title
                    ];

                }

                $attributes[$attributeValueProduct->attribute_id]['values'][] = [
                    'value_id' => $attributeValueProduct->attribute_value_id,
                    'value'    => $attributeValueProduct->value,
                    'title'    => $attributeValueProduct->attributeValueTranslation?->title
                ];

            }


            if ($product->rate_price < $min || $min == 0) {
                $min = $product->rate_price;
            }

            if ($product->rate_price > $max || $max == 0) {
                $max = $product->rate_price;
            }

        }

        $categories = collect($categories)->sortDesc()->values()->toArray();
        $attributes = collect($this->removeDuplicates($attributes))->sortDesc()->values()->toArray();

        return [
            'brands'      => collect($brands)->sortDesc()->values()->toArray(),
            'categories'  => $categories,
            'attributes'  => $attributes,
            'price'       => [
                'min' => $min * $this->currency(),
                'max' => $max * $this->currency(),
            ],
            'count' => $products->count(),
        ];
    }

    public function removeDuplicates($array): array
    {
        $seen = [];

        return array_filter($array, function ($item) use (&$seen) {

            foreach ($item['values'] as $value) {
                // Определяем уникальный ключ на основе условия
                $uniqueKey = null;

                if ($value['value_id'] !== null) {
                    $uniqueKey = 'value_id:' . $value['value_id'];
                } elseif ($value['value'] !== null && $value['title'] !== null) {
                    $uniqueKey = 'value:' . $value['value'] . '_title:' . $value['title'];
                }

                // Если уникальный ключ уже был добавлен, пропускаем элемент
                if ($uniqueKey !== null && in_array($uniqueKey, $seen)) {
                    return false;
                }

                // Добавляем уникальный ключ в список увиденных и сохраняем элемент
                if ($uniqueKey !== null) {
                    $seen[] = $uniqueKey;
                }
            }

            return true;
        });
    }

    public function search(array $filter): array
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $search = data_get($filter, 'search');

        $productValues  = [];
        $categoryValues = [];

        $categoryColumn  = data_get($filter, 'c_column', 'id');
        $brandColumn     = data_get($filter, 'b_column', 'id');
        $attributeColumn = data_get($filter, 'a_column', 'id');

        if ($categoryColumn !== 'id') {
            $categoryColumn = Schema::hasColumn('categories', $categoryColumn) ? $categoryColumn : 'id';
        }

        if ($brandColumn !== 'id') {
            $brandColumn = Schema::hasColumn('brands', $brandColumn) ? $brandColumn : 'id';
        }

        if ($attributeColumn !== 'id') {
            $attributeColumn = Schema::hasColumn('attributes', $attributeColumn) ? $attributeColumn : 'id';
        }

        $categories = Category::whereHas('translation', fn($q) => $q
                ->select(['id', 'category_id', 'locale', 'title'])
                ->where('title', 'LIKE', "%$search%")
                ->where('locale', $this->language)
            )
            ->where('active', true)
            ->orderBy($categoryColumn, data_get($filter, 'c_sort', 'desc'))
            ->paginate(data_get($filter, 'c_perPage', 10))
            ->items();

        $brands = Brand::select(['id', 'title', 'active'])
            ->where('title', 'LIKE', "%$search%")
            ->where('active', true)
            ->orderBy($brandColumn, data_get($filter, 'b_sort', 'desc'))
            ->paginate(data_get($filter, 'b_perPage', 10))
            ->items();

        $attributes = Attribute::with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'attributeValues.translation' => fn($q) => $q->where('locale', $this->language),
        ])
            ->orderBy($attributeColumn, data_get($filter, 'a_sort', 'desc'))
            ->paginate(data_get($filter, 'a_perPage', 10))
            ->items();

        foreach ($categories as $category) {
            /** @var Category $category */
            $categoryValues[] = [
                'id'    => $category->id,
                'title' => $category->translation?->title
            ];
        }

        return [
            'products'   => $productValues,
            'categories' => $categoryValues,
            'brands'     => $brands,
            'attributes' => $attributes,
        ];
    }

}
