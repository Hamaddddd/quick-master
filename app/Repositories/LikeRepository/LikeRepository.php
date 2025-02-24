<?php
declare(strict_types=1);

namespace App\Repositories\LikeRepository;

use App\Models\Language;
use App\Models\Like;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class LikeRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Like::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Like $model */
        $model  = $this->model();
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('likes', $column) ? $column : 'id';
        }

        return $model
            ->filter($filter)
            ->with($this->getWithByType(data_get($filter, 'type')))
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }


    private function getWithByType(?string $type = 'product'): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $with = [
            'likable'
        ];

        if ($type === 'product') {
            $with = [
                'likable' => fn($q) => $q->actual($this->language),
                'likable.translation' => fn($q) => $q->where('locale', $this->language),
                'likable.region.translation' => fn($q) => $q->where('locale', $this->language),
                'likable.country.translation' => fn($q) => $q->where('locale', $this->language),
                'likable.city.translation' => fn($q) => $q->where('locale', $this->language),
                'likable.area.translation' => fn($q) => $q->where('locale', $this->language),
            ];
        }


        return $with;
    }
}
