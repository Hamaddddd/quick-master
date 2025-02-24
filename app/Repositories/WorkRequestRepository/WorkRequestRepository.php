<?php
declare(strict_types=1);

namespace App\Repositories\WorkRequestRepository;

use App\Models\Language;
use App\Models\WorkRequest;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkRequestRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return WorkRequest::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        return WorkRequest::filter($filter)->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param WorkRequest $model
     * @return WorkRequest
     */
    public function show(WorkRequest $model): WorkRequest
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->load([
            'product.translation' => fn($query) => $query
                ->where('locale', $this->language),
        ]);
    }

    /**
     * @param int $id
     * @param int|null $ownerId
     * @return WorkRequest|null
     */
    public function showById(int $id, ?int $ownerId = null): ?WorkRequest
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return WorkRequest::with([
            'product.translation' => fn($query) => $query
                ->where('locale', $this->language),
        ])
            ->when($ownerId, fn($q) => $q->where('owner_id', $ownerId))
            ->find($id);
    }

}
