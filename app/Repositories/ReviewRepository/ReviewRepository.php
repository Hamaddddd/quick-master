<?php
declare(strict_types=1);

namespace App\Repositories\ReviewRepository;

use App\Helpers\Utility;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ReviewRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Review::class;
    }

    public function paginate(array $filter, array $customWith = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $with = [
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
            'galleries'
        ];

        $orderWith = [
            'reviewable' => fn($q) => $q->select([
                'id',
                'uuid',
                'type',
            ]),
            'reviewable.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
            ])
                ->where('locale', $this->language),
            'reviewable.translations' => fn($q) => $q->select(['id']),
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
//            'assignable.translation' => fn($q) => $q
//                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
//                    $q->where('locale', $this->language)->orWhere('locale', $locale);
//                })),
        ];

        $productWith = [
            'reviewable' => fn($q) => $q->select([
                'id',
                'uuid',
                'img',
            ]),
            'reviewable.translations' => fn($q) => $q->select(['id']),
            'reviewable.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
                'description',
                'product_id',
            ])
                ->where('locale', $this->language),
            'assignable.translation' => fn($q) => $q
                ->where('locale', $this->language),
        ];

        if (count($customWith) > 0) {
            $with = $customWith;
        } else if (data_get($filter, 'type') === 'order') {
            $with += $orderWith;
        } else if (data_get($filter, 'type') === 'product') {
            $with += $productWith;
        } else if (data_get($filter, 'assign') === 'assign') {
            $with += [
                'assignable.translation' => fn($q) => $q
                    ->where('locale', $this->language),
            ];
        }

        /** @var Review $reviews */
        $reviews = $this->model();

        $column = data_get($filter, 'column', 'id');

        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if ($column !== 'user' || empty($user?->id)) {
            $column = Schema::hasColumn('reviews', $column) ? $column : 'id';
        }

        return $reviews
            ->filter($filter)
            ->with($with)
            ->when(
                $column === 'user' && !empty($user?->id),
                fn($q) => $q->orderByRaw(DB::raw("FIELD(user_id, $user?->id) DESC")),
                fn($q) => $q->orderBy($column, data_get($filter, 'sort', 'desc'))
            )
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function show(Review $review): Review
    {
        return $review->loadMissing(['reviewable', 'assignable', 'galleries', 'user']);
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'assignable_id'   => $id,
            'assignable_type' => User::class,
        ]);
    }

    public function addedReview(array $filter): array
    {
        $sellerId = data_get($filter, 'seller_id');
        $type     = data_get($filter, 'type');
        $typeId   = data_get($filter, 'type_id');

        if (empty($sellerId)) {
            return [
                'ordered'      => false,
                'added_review' => false,
            ];
        }

        $ordered = match ($type) {
            'seller'  => Order::where('seller_id', $sellerId)->exists(),
            'product' => Order::whereIn('product_id', $typeId)->exists(),
            default   => false,
        };

        $addedReview = Review::filter($filter)->exists();

        return [
            'ordered'       => $ordered,
            'added_review'  => $addedReview,
        ];
    }
}
