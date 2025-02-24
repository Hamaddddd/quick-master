<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Feedback
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property boolean $helpful
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Product $product
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Feedback extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'helpful' => 'bool'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeFilter($query, $filter) {
        return $query
            ->when(isset($filter['user_id']),    fn($q) => $q->where('user_id',    $filter['user_id']))
            ->when(isset($filter['product_id']), fn($q) => $q->where('product_id', $filter['product_id']))
            ->when(isset($filter['helpful']),    fn($q) => $q->where('helpful',    $filter['helpful']))
            ->when(isset($filter['date_from']),  fn($q) => $q->where('created_at', '>=', $filter['date_from'])->where('created_at', '<=', $filter['date_to'] ?? now()))
            ;
    }
}
