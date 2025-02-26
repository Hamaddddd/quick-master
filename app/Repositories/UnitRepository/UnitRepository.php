<?php
declare(strict_types=1);

namespace App\Repositories\UnitRepository;

use App\Models\Language;
use App\Models\Unit;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UnitRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }

    /**
     * Get Units with pagination
     */
    public function unitsPaginate(array $filter = []): LengthAwarePaginator
    {
       if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
           abort(403);
       }

        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->with([
                'translation' => fn($q) => $q
                    ->where('locale', $this->language)
            ])
            ->when(data_get($filter, 'active'), fn($q, $active) => $q->where('active', $active))
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->whereHas('translations', fn($q) => $q->where('title', 'LIKE', "%$search%"));
            })
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get Unit by Identification
     */
    public function unitDetails(int $id): Model|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->with([
                'translation' => fn($q) => $q
                    ->where('locale', $this->language)
            ])
            ->find($id);
    }

}
