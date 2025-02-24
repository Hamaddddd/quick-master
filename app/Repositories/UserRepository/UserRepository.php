<?php
declare(strict_types=1);

namespace App\Repositories\UserRepository;

use App\Models\Language;
use App\Models\Notification;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @param string|int $id
     * @param bool $isUuid
     * @return User|null
     */
    public function userById(string|int $id, bool $isUuid = false): ?User
    {
        /** @var User $user */
        $user = $this->model()
            ->with([
                'roles',
                'wallet',
                'emailSubscription',
                'notifications',
                'currency',
            ])
            ->when($isUuid, fn($q) => $q->where('uuid', $id), fn($q) => $q->where('id', $id))
            ->first();

        if (empty($user?->wallet)) {
            return $user;
        }

        $referralActive = (int)Settings::where('key', 'referral_active')->first()?->value;

        if ($referralActive) {

            /** @var Referral $referral */
            $referral = Referral::with([
                'translation' => fn($query) => $query->where('locale', $this->language),
                'translations',
                'galleries',
            ])->where([
                ['expired_at', '>=', now()],
            ])->first();

            if ($referral?->id) {
                $rate         = $user->wallet->currency?->rate ?? 1;

                $fromTopUp    = $user->wallet->histories?->where('type','referral_from_topup');
                $fromWithdraw = $user->wallet->histories?->where('type','referral_from_withdraw');

                $toTopUp      = $user->wallet->histories?->where('type','referral_to_topup');
                $toWithdraw   = $user->wallet->histories?->where('type','referral_to_withdraw');

                $user
                    ->setAttribute('referral_from_topup_price', $fromTopUp?->sum('price') * $rate)
                    ->setAttribute('referral_from_withdraw_price', $fromWithdraw?->sum('price') * $rate)
                    ->setAttribute('referral_from_topup_count', $fromTopUp?->count())
                    ->setAttribute('referral_from_withdraw_count', $fromWithdraw?->count())

                    ->setAttribute('referral_to_topup_price', $toTopUp?->sum('price') * $rate)
                    ->setAttribute('referral_to_withdraw_price', $toWithdraw?->sum('price') * $rate)
                    ->setAttribute('referral_to_topup_count', $toTopUp?->count())
                    ->setAttribute('referral_to_withdraw_count', $toWithdraw?->count());
            }

            // for UserResource
            request()->offsetSet('referral', 1);
        }

        return $user;
    }

    public function chatShowById(int $id) {
        return User::select([
            'id',
            'firstname',
            'lastname',
            'img',
            'active',
        ])->find($id);
    }

    public function chatUsersGet(array $ids): \Illuminate\Support\Collection
    {
        return User::select([
            'id',
            'firstname',
            'lastname',
            'img',
            'active',
        ])
            ->whereIn('id', $ids)
            ->get();
    }

    public function adminInfo(): ?User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->select([
                'id',
                'firstname',
                'lastname',
                'img',
            ])
            ->first();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function usersPaginate(array $filter = []): LengthAwarePaginator
    {
        return $this->model()
            ->filter($filter)
            ->with([
                'wallet',
                'roles' => fn($q) => $q->when(data_get($filter, 'role'), fn ($q, $role) => $q->where('name', $role))
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function usersSearch(array $filter): LengthAwarePaginator
    {
        return $this->model()
            ->filter($filter)
            ->with([
                'roles' => fn($q) => $q->when(data_get($filter, 'roles'), function ($q, $roles) {
                    $q->whereIn('name', (array)$roles);
                })
            ])
            ->orderBy(data_get($filter,'column','id'), data_get($filter,'sort','desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @return Collection|Notification[]
     */
    public function usersNotifications(): array|Collection
    {
        return Notification::get();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function searchSending(array $filter): LengthAwarePaginator
    {
        return $this->model()
            ->filter($filter)
            ->whereHas('wallet')
            ->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'img',
            ])
            ->where('id', '!=', auth('sanctum')->id())
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @return int[]
     */
    public function notificationStatistic(): array
    {
        $notification = DB::table('push_notifications')
            ->select([
                DB::raw('count(id) as count'),
                DB::raw("sum(if(type = 'new_order', 1, 0)) as total_new_order_count"),
                DB::raw("sum(if(type = 'new_user_by_referral', 1, 0)) as total_new_user_by_referral_count"),
                DB::raw("sum(if(type = 'status_changed', 1, 0)) as total_status_changed_count"),
                DB::raw("sum(if(type = 'news_publish', 1, 0)) as total_news_publish_count"),
            ])
            ->whereNull('read_at')
            ->where('user_id', auth('sanctum')->id())
            ->first();

        $transaction = DB::table('transactions')
            ->select([
                DB::raw('count(id) as count'),
            ])
            ->where('status', Transaction::STATUS_PROGRESS)
            ->where('user_id', auth('sanctum')->id())
            ->first();

        return [
            'notification'          => (int)$notification?->count,
            'new_order'             => (int)$notification?->total_new_order_count,
            'new_user_by_referral'  => (int)$notification?->total_new_user_by_referral_count,
            'status_changed'        => (int)$notification?->total_status_changed_count,
            'news_publish'          => (int)$notification?->total_news_publish_count,
            'transaction'           => (int)$transaction?->count
        ];

    }
}
