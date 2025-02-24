<?php
declare(strict_types=1);

namespace App\Repositories\DashboardRepository;

use App\Models\User;
use App\Models\Review;
use App\Models\Product;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;

class DashboardRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Feedback::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function statistics(array $filter = []): array
    {
        $time = data_get($filter, 'time', 'subYear');
        $time = now()->{$time}()->format('Y-m-d 00:00:01');

        $timeFormat = $time == 'subYear' ? "%Y" : ($time == 'subMonth' ? "%Y-%m" : "%Y-%m-%d");

        $productsCount = Product::when(isset($filter['user_id']), fn($q) => $q->where('user_id', $filter['user_id']))
            ->count('id');

        $reviews = Review::when(isset($filter['user_id']), fn($q) => $q->where('user_id', $filter['user_id']))
            ->count('id');

        $feedBacks = Feedback::filter($filter)
            ->select([
                DB::raw('count(id) as count'),
                DB::raw("sum(if(helpful = 0, 1, 0)) as not_helpful"),
                DB::raw("sum(if(helpful = 1, 1, 0)) as helpful"),
                DB::raw("(DATE_FORMAT(created_at, '$timeFormat')) as time"),
            ])
            ->groupBy('time')
            ->get();

        /** @var object $feedBacks */
        return [
            'count'          => $feedBacks?->sum('count'),
            'not_helpful'    => $feedBacks?->sum('not_helpful'),
            'helpful'        => $feedBacks?->sum('helpful'),
            'chart'          => $feedBacks,
            'products_count' => $productsCount,
            'reviews_count'  => $reviews
        ];
    }

    public function productsStatistic(array $filter = []): Paginator
    {
        $perPage = $filter['perPage'] ?? 5;

        return Product::with([
            'translation' => fn($q) => $q
                ->select(['id', 'locale', 'title', 'product_id'])
                ->when($this->language, fn ($q) => $q->where('locale', $this->language)),
        ])
            ->when(isset($filter['user_id']), fn($q) => $q->where('user_id', $filter['user_id']))
            ->select([
                'id',
                'price',
                'email',
                'contact_name',
                'views_count',
                'phone_views_count',
                'message_click_count',
            ])
            ->withCount('likes')
            ->paginate($perPage);
    }

    public function usersStatistic(array $filter = []): Paginator
    {
        $perPage = $filter['perPage'] ?? 5;

        return User::query()
            ->select([
                'firstname',
                'id',
                'img',
                'lastname',
                'phone',
            ])
            ->when(isset($filter['user_id']), fn($q) => $q->where('id', $filter['user_id']))
            ->withCount(['feedBacks' => fn($q) => $q->filter($filter)])
            ->withSum  (['feedBacks as not_helpful' => fn($q) => $q->filter($filter)->where('helpful', 0)], 'helpful')
            ->withSum  (['feedBacks as helpful' => fn($q) => $q->filter($filter)->where('helpful', 1)], 'helpful')
            ->paginate($perPage);
    }

}
