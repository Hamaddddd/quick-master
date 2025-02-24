<?php
declare(strict_types=1);

namespace App\Repositories\TagRepository;

use App\Models\Language;
use App\Models\Tag;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TagRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Tag::class;
    }

    public function paginate($data = []): LengthAwarePaginator
    {
        /** @var Tag $tags */
        $tags = $this->model();

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        $locale = Language::where('default', 1)->first()?->locale;

        return $tags
            ->with([
                'product' => fn ($q) => $q->select(['id', 'slug', 'user_id', 'category_id', 'brand_id', 'unit_id']),
                'translation' => fn($q) => $q
                    ->where('locale', $this->language)
            ])
            ->when(data_get($data, 'product_id'),
                fn(Builder $q, $productId) => $q->where('product_id', $productId)
            )
            ->when(data_get($data, 'user_id'),
                fn(Builder $q, $id) => $q->whereHas('product', fn($b) => $b->where('user_id', $id))
            )
            ->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
            ->orderBy(data_get($data, 'column', 'id'), data_get($data, 'sort', 'desc'))
            ->paginate(data_get($data, 'perPage', 15));
    }

    public function show(Tag $tag): Tag
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $tag->load([
            'product',
            'translation' => fn($q) => $q
                ->where('locale', $this->language)
        ]);
    }
}
