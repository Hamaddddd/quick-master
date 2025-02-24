<?php
declare(strict_types=1);

namespace App\Repositories\StoryRepository;

use App\Models\Language;
use App\Models\Product;
use App\Models\Story;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

class StoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Story::class;
    }

    /**
     * @param array $data
     * @param string $type
     * @return mixed
     */
    public function index(array $data = [], string $type = 'paginate'): mixed
    {
        /** @var Story $stories */
        $stories = $this->model();

        $paginate = data_get([
            'paginate'       => 'paginate',
            'simplePaginate' => 'simplePaginate'
        ], $type, 'paginate');

        $locale = Language::where('default', 1)->first()?->locale;

        return $stories
            ->with([
                'translations',
                'translation' => fn ($q) => $q->where('locale', $this->language),
                'product' => fn ($q) => $q->select(['id', 'slug']),
                'product.translation' => fn ($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),
            ])
            ->select([
                'id',
                'product_id',
                'active',
                'file_urls',
            ])
            ->when(data_get($data, 'product_id'), fn(Builder $q, $productId) => $q->where('product_id', $productId))
            ->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
            ->orderBy(data_get($data, 'column', 'id'), data_get($data, 'sort', 'desc'))
            ->$paginate(data_get($data, 'perPage', 15));
    }

    public function list(array $data = []): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $stories = Story::with([
            'translation' => fn($query) => $query
                ->where('locale', $this->language),
            'product' => fn($q) => $q->select([
                'id',
                'slug',
                'active',
                'status',
            ])
                ->where('active', 1)
                ->where('status', Product::PUBLISHED),
            'product.user:id,img,firstname,lastname',
            'product.translation' => fn($query) => $query
                ->where('locale', $this->language)
                ->select('id', 'product_id', 'locale', 'title'),
        ])
            ->where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))
            ->orderByDesc('id')
            ->simplePaginate(data_get($data, 'perPage', 100));

        $result = [];

        foreach ($stories as $story) {

            /** @var Story $story */

            $product = $story->product;

            if ($product && (!$product->active || $product->status !== Product::PUBLISHED)) {
                continue;
            }

            $title        = $story?->translation?->title;
            $productTitle = $product?->translation?->title;
            $user         = $product?->user;

            foreach ($story->file_urls as $fileUrl) {

                $result[$user?->id][] = [
                    'firstname'     => $user?->firstname,
                    'lastname'      => $user?->lastname,
                    'avatar'        => $user?->img,
                    'color'         => $story?->color,
                    'title'         => $title,
                    'product_title' => $productTitle,
                    'url'           => $fileUrl,
                    'created_at'    => $story->created_at?->format('Y-m-d H:i:s') . 'Z',
                    'updated_at'    => $story->updated_at?->format('Y-m-d H:i:s') . 'Z',
                ];

            }

        }

        $result = collect($result);

        return $result?->count() > 0 ? array_values($result->reject(fn($items) => empty($items))->toArray()) : [];
    }

    public function show(Story $story): Story
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $story->load([
            'translation' => fn($q) => $q
                ->where('locale', $this->language),
            'translations',
            'product.user:id,img,firstname,lastname',
            'product.translation' => fn($q) => $q
                ->where('locale', $this->language),
        ]);
    }
}
