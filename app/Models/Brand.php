<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\MetaTagable;
use Database\Factories\BrandFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Brand
 *
 * @property int $id
 * @property string $slug
 * @property string $uuid
 * @property string $title
 * @property int $active
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static BrandFactory factory(...$parameters)
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Brand extends Model
{
    use HasFactory, Loadable, MetaTagable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function scopeUpdatedDate($query, $updatedDate)
    {
        return $query->where('updated_at', '>', $updatedDate);
    }

    /* Filter Scope */
    public function scopeFilter($value, $filter)
    {
        return $value
            ->when(data_get($filter, 'search'), fn($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ->when(data_get($filter, 'slug'), fn($q, $slug) => $q->where('slug', $slug))
            ->when(isset($filter['active']), fn($q) => $q->whereActive($filter['active']))
            ->when(data_get($filter, 'category_id'), function ($q, $categoryId) {
                $q->whereHas('products', fn($q) => $q
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
                    ->where('category_id', $categoryId)
                );
            })
            ->when(data_get($filter,'column'), function (Builder $query, $column) use($filter) {
                $query->orderBy($column, data_get($filter, 'sort', 'desc'));
            }, fn($query) => $query->orderBy('id', 'desc'));
    }

}
