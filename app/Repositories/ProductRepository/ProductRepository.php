<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

use App\Models\Language;
use App\Models\Product;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
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
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language),
                'translations',
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($query) => $query->where('locale', $this->language),
                'tags.translation' => fn($q) => $q
                    ->select('id', 'category_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'productAdsPackage' => fn($q) => $q->where('expired_at', '>', now()),
                'productAdsPackage.userAdsPackage' => fn($q) => $q->select('id', 'ads_package_id', 'count'),
                'productAdsPackage.userAdsPackage.adsPackage' => fn($q) => $q->select('id'),
                'productAdsPackage.userAdsPackage.adsPackage.translation' => fn($q) => $q
                    ->select('id', 'ads_package_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'city.translation' => fn($query) => $query->where('locale', $this->language),
                'area.translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->withCount('likes')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $slug
     * @return Product|Builder|Model|null
     */
    public function productBySlug(string $slug): Model|Builder|Product|null
    {
        /** @var Product $product */
        $product = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $product
            ->with([
                'region.translation'  => fn($q) => $q->where('locale', $this->language),
                'country.translation' => fn($q) => $q->where('locale', $this->language),
                'city.translation'    => fn($q) => $q->where('locale', $this->language),
                'area.translation'    => fn($q) => $q->where('locale', $this->language),
                'user:id,firstname,lastname',
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q
                    ->where('locale', $this->language),
                'productAdsPackage' => fn($q) => $q->where('expired_at', '>', now()),
                'productAdsPackage.userAdsPackage' => fn($q) => $q->select('id', 'ads_package_id', 'count'),
                'productAdsPackage.userAdsPackage.adsPackage' => fn($q) => $q->select('id'),
                'productAdsPackage.userAdsPackage.adsPackage.translation' => fn($q) => $q
                    ->select('id', 'ads_package_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'translation' => fn($query) => $query
                    ->where('locale', $this->language),
                'tags.translation' => fn($q) => $q
                    ->select('id', 'category_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
            ])
            ->withCount('likes')
            ->firstWhere('slug', $slug)
            ?->setAttribute('get_attributes', true);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsSearch(array $filter = []): LengthAwarePaginator
    {
        return $this->model()
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->select([
                        'id',
                        'product_id',
                        'locale',
                        'title',
                    ])
                    ->where('locale', $this->language),
            ])
            ->latest()
            ->select([
                'id',
                'img',
                'slug',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

}

