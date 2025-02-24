<?php
declare(strict_types=1);

namespace App\Repositories\AttributeRepository;

use App\Models\Attribute;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AttributeRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Attribute::class;
    }

    /**
     * @return array
     */
    private function getWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'translations',
            'translation' => fn($q) => $q
                ->where('locale', $this->language),
            'category',
            'category.translation' => fn($q) => $q
                ->where('locale', $this->language),
            'attributeValues',
            'attributeValues.translations',
            'attributeValues.translation' => fn($q) => $q
                ->where('locale', $this->language),
        ];
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function index(array $filter): LengthAwarePaginator
    {
        $model  = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $model
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->where('locale', $this->language),
                'category',
                'category.translation' => fn($q) => $q
                    ->where('locale', $this->language),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with($this->getWith())
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Attribute $attribute
     * @return Model|null
     */
    public function show(Attribute $attribute): Model|null
    {
        return $attribute->loadMissing($this->getWith());
    }

    /**
     * @param int $id
     * @return Collection|Builder[]
     */
    public function showByCategoryId(int $id): array|Collection
    {
        return Attribute::with($this->getWith())
            ->where('category_id', $id)
            ->get();
    }

}
