<?php
declare(strict_types=1);

namespace App\Repositories\BannerRepository;

use App\Models\Banner;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class BannerRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Banner::class;
    }

    public function bannersPaginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        if (!isset($filter['type'])) {
            $filter['type'] = Banner::BANNER;
        }

        /** @var Banner $model */
        $model = $this->model();

        return $model
            ->with([
                'translation' => fn($q) => $q
                    ->where('locale', $this->language),
            ])
            ->when(request()->is('api/v1/rest/*'), function ($query) use ($filter) {
                $query->whereHas('products', fn($q) => $q
                    ->actual($this->language)
                    ->when(data_get($filter, 'product_ids'), fn ($q, $ids) => $q->whereIn('id', $ids))
                );
            })
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(
                data_get($filter, 'type'),
                fn($q, $type) => $q->where('type', $type), fn($q) => $q->where('type', Banner::BANNER)
            )
            ->select([
                'id',
                'url',
                'type',
                'img',
                'active',
                'created_at',
                'updated_at',
                'clickable',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function bannerDetails(int $id): Model|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->withCount('likes')
            ->withCount('products')
            ->with([
                'galleries',
                'translation' => fn($query) => $query
                    ->where('locale', $this->language),
                'translations',
            ])
            ->find($id);
    }
}
