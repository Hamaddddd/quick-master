<?php
declare(strict_types=1);

namespace App\Models;

use Schema;
use Eloquent;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Attribute
 *
 * @property int $id
 * @property string $type
 * @property bool $required
 * @property int $category_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $category
 * @property-read AttributeTranslation|null $translation
 * @property-read Collection|AttributeTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|AttributeValue[] $attributeValues
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @mixin Eloquent
 */
class Attribute extends Model
{
    public $guarded = ['id'];

    const DROP_DOWN = 'drop_down';
    const CHECKBOX  = 'checkbox';
    const RADIO     = 'radio';
    const FROM_TO   = 'from_to';
    const YES_OR_NO = 'yes_or_no';
    const INPUT     = 'input';

    const TYPES = [
        self::INPUT     => self::INPUT,
        self::DROP_DOWN => self::DROP_DOWN,
        self::CHECKBOX  => self::CHECKBOX,
        self::RADIO     => self::RADIO,
        self::FROM_TO   => self::FROM_TO,
        self::YES_OR_NO => self::YES_OR_NO,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // attributeValues
    public function attributeValues(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(AttributeTranslation::class);
    }

    public function scopeFilter($query, array $filter): void
    {
        $column = $filter['column'] ?? 'id';

        if (!Schema::hasColumn('attributes', $column)) {
            $column = 'id';
        }

        $query
            ->when(isset($filter['required']), fn($q, $id) => $q->where('required', $filter['required']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'area_id', 'locale', 'title');
                });
            })
            ->when(data_get($filter, 'category_id'),  fn($q, $id)  => $q->where('category_id',   $id))
            ->when(data_get($filter, 'category_ids'), fn($q, $ids) => $q->whereIn('category_id', $ids))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }

}
