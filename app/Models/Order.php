<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use App\Traits\Reviewable;
use App\Traits\UserSearch;
use Database\Factories\OrderFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $currency_id
 * @property int $seller_id
 * @property string $canceled_note
 * @property double $total_price
 * @property float $rate
 * @property array $location
 * @property array $address
 * @property string $phone
 * @property string $username
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $rate_total_price
 * @property-read Currency|null $currency
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int $transactions_count
 * @property-read Product $product
 * @property-read User|null $user
 * @property-read User|null $seller
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static OrderFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Order extends Model
{
    use HasFactory, Payable, Reviewable, UserSearch;

    protected $guarded = ['id'];

    protected $casts = [
        'location'       => 'array',
        'address'        => 'array',
    ];

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_price;
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    /**
     * @param $query
     * @param array $filter
     * @return void
     */
    public function scopeFilter($query, array $filter): void
    {
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('orders', $column) ? $column : 'id';
        }

        $sellerIds = [];

        $query
            ->when(isset($filter['current']),    fn($q) => $q->where('current', $filter['current']))
            ->when(isset($filter['type']),       fn($q) => $q->where('type', $filter['type']))
            ->when(isset($filter['parent']),     fn($q) => $filter['parent'] ? $q->whereNull('parent_id') : $q->whereNotNull('parent_id'))
            ->when(isset($filter['parent_id']),  fn($q) => $q->where('parent_id', $filter['parent_id']))
            ->when(isset($filter['track_name']), fn($q) => $q->where('track_name', $filter['track_name']))
            ->when(isset($filter['track_id']),   fn($q) => $q->where('track_id', $filter['track_id']))
            ->when(isset($filter['track_url']),  fn($q) => $q->where('track_url', $filter['track_url']))
            ->when(data_get($filter, 'seller_id'),  fn($q, $id)  => $q->where('seller_id', $id))
            ->when(data_get($filter, 'seller_ids'), fn($q, $ids) => $q->whereIn('seller_id', (array)$ids))
            ->when(!isset($filter['seller_id']) && !isset($filter['seller_ids']), function ($q) use ($sellerIds) {
                $q->whereIn('seller_id', $sellerIds);
            })
            ->when(data_get($filter, 'payment_id'), function ($q, $paymentId) {
                $q->whereHas('transactions', fn($q) => $q->where('payment_sys_id', $paymentId));
            })
            ->when(data_get($filter, 'payment_status'), function ($q, $status) {
                $q->whereHas('transactions', fn($q) => $q->where('status', $status));
            })
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', (int)$userId))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo));

                $query
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            })
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($b) use ($search) {
                    $b
                        ->where('id', $search)
                        ->orWhere('user_id', $search)
                        ->orWhere('phone', "%$search%")
                        ->orWhere('username', "%$search%")
                        ->orWhere('note', 'LIKE', "%$search%")
                        ->orWhereHas('user', fn($q) => $this->search($q, $search));
                });
            })
            ->when($column, function ($q, $column) use ($filter) {
                $q->orderBy($column, data_get($filter, 'sort', 'desc'));
            });
    }

}
