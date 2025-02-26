<?php
declare(strict_types=1);

namespace App\Repositories\CareerRepository;

use App\Models\Career;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CareerRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Career::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var Career $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($query) => $query->where('locale', $this->language),
                'category.translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Career $model
     * @return Career|null
     */
    public function show(Career $model): Career|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->loadMissing([
            'translations',
            'translation' => fn($query) => $query->where('locale', $this->language),
            'category.translation' => fn($query) => $query->where('locale', $this->language),
        ]);
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function showById(int $id): ?Model
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return Career::with([
            'translations',
            'translation' => fn($query) => $query->where('locale', $this->language),
            'category.translation' => fn($query) => $query->where('locale', $this->language),
        ])
            ->where('id', $id)
            ->first();

    }
}
