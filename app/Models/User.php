<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\UserSearch;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $uuid
 * @property string $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $birthday
 * @property string $gender
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property string|null $ip_address
 * @property boolean $active
 * @property string|null $img
 * @property array|null $firebase_token
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $name_or_email
 * @property string|null $verify_token
 * @property string|null $referral
 * @property string|null $my_referral
 * @property string|null $full_name
 * @property int|null $r_count
 * @property int|null $r_avg
 * @property int|null $r_sum
 * @property int|null $o_count
 * @property int|null $o_sum
 * @property int|null $currency_id
 * @property string|null $lang
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property mixed $role
 * @property-read Collection|Banner[] $likes
 * @property-read int|null $likes_count
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read Collection|Review[] $assignReviews
 * @property-read int|null $assign_reviews_count
 * @property-read Collection|Notification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|Order[] $orders
 * @property-read int|null $orders_count
 * @property-read int|null $reviews_avg_rating
 * @property-read int|null $assign_reviews_avg_rating
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection|Permission[] $transactions
 * @property-read int|null $transactions_count
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 * @property-read Collection|Feedback[] $feedBacks
 * @property-read int|null $feed_backs_count
 * @property-read EmailSubscription|null $emailSubscription
 * @property-read Collection|SocialProvider[] $socialProviders
 * @property-read int|null $social_providers_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read Collection|PaymentProcess[] $paymentProcess
 * @property-read int $payment_process_count
 * @property-read int|null $tokens_count
 * @property-read Wallet|HasOne|null $wallet
 * @property-read int|null $referral_from_topup_price
 * @property-read int|null $referral_from_withdraw_price
 * @property-read int|null $referral_to_withdraw_price
 * @property-read int|null $referral_to_topup_price
 * @property-read int|null $referral_from_topup_count
 * @property-read int|null $referral_from_withdraw_count
 * @property-read int|null $referral_to_withdraw_count
 * @property-read int|null $referral_to_topup_count
 * @property-read int|null $not_helpful
 * @property-read int|null $helpful
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self permission($permissions)
 * @method static Builder|self query()
 * @method static Builder|self filter($filter)
 * @method static Builder|self role($roles, $guard = null)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereBirthday($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereEmail($value)
 * @method static Builder|self whereEmailVerifiedAt($value)
 * @method static Builder|self whereFirebaseToken($value)
 * @method static Builder|self whereFirstname($value)
 * @method static Builder|self whereGender($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereIpAddress($value)
 * @method static Builder|self whereLastname($value)
 * @method static Builder|self wherePassword($value)
 * @method static Builder|self wherePhone($value)
 * @method static Builder|self wherePhoneVerifiedAt($value)
 * @method static Builder|self whereRememberToken($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens,
        HasFactory,
        HasRoles,
        Loadable,
        UserSearch;

    const DATES = [
        'subMonth'  => 'subMonth',
        'subWeek'   => 'subWeek',
        'subYear'   => 'subYear',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime:Y-m-d H:i:s',
        'phone_verified_at' => 'datetime:Y-m-d H:i:s',
        'birthday'          => 'datetime:Y-m-d H:i:s',
        'firebase_token'    => 'array',
        'active'            => 'bool',
    ];

    public function isOnline(): ?bool
    {
        return Cache::has('user-online-' . $this->id);
    }

    public function getRoleAttribute(): string
    {
        return $this->role = $this->roles?->last()?->name ?? 'no role';
    }

    public function getNameOrEmailAttribute(): ?string
    {
        return $this->firstname ?? $this->email;
    }

    public function getFullNameAttribute(): ?string
    {
        return "$this->firstname $this->lastname";
    }

    public function emailSubscription(): HasOne
    {
        return $this->hasOne(EmailSubscription::class);
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, NotificationUser::class)
            ->as('notification')
            ->withPivot('active');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function assignReviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'assignable');
    }

    public function socialProviders(): HasMany
    {
        return $this->hasMany(SocialProvider::class,'user_id','id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class,'user_id');
    }

    public function paymentProcess(): HasMany
    {
        return $this->hasMany(PaymentProcess::class);
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class, Like::class);
    }

    public function activity(): HasOne
    {
        return $this->hasOne(UserActivity::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    public function feedBacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(data_get($filter, 'role'), function ($query, $role) {
                $query->whereHas('roles', fn($q) => $q->where('name', $role));
            })
            ->when(data_get($filter, 'roles'), function ($q, $roles) {
                $q->whereHas('roles', function ($q) use($roles) {
                    $q->whereIn('name', is_array($roles) ? $roles : [$roles]);
                });
            })
            ->when(data_get($filter, 'search'), function ($q, $search) {
                return $this->search($q, $search);
            })
            ->when(data_get($filter, 'date_from'), function ($query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
                $dateTo   = data_get($filter, 'date_to', date('Y-m-d'));
                $dateTo   = date('Y-m-d', strtotime($dateTo . ' +1 day'));

                return $query->whereHas('orders', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('created_at', '>=', $dateFrom)->where('created_at', '<=', $dateTo);
                });
            })
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'exist_token'), fn($query) => $query->whereNotNull('firebase_token'))
            ->when(data_get($filter, 'walletSort'), function ($q, $walletSort) use($filter) {
                $q->whereHas('wallet', function ($q) use($walletSort, $filter) {
                    $q->orderBy($walletSort, data_get($filter, 'sort', 'desc'));
                });
            })
            ->when(data_get($filter,'column', 'id'), function (Builder $query, $column) use($filter) {

                $column = match ($column) {
                    'rating'     => 'r_avg',
                    'count'      => 'o_count',
                    'sum'        => 'o_sum',
                    'wallet_sum' => 'wallet_sum_price',
                    default      => Schema::hasColumn('users', $column) ? $column : 'id',
                };

                $query->orderBy($column, data_get($filter, 'sort', 'desc'));

                if (data_get($filter, 'by_rating')) {

                    $operator = data_get($filter, 'by_rating') === 'top' ? '>=' : '<';

                    return $query->having('r_avg', $operator, 3.99);

                }

                return $query;
            });
    }
}
