<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\MetaTagable;
use Database\Factories\CategoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Category
 *
 * @property int $id
 * @property string $slug
 * @property string $uuid
 * @property string|null $keywords
 * @property int|null $parent_id
 * @property int|null $age_limit
 * @property int|null $work
 * @property int $type
 * @property string|null $img
 * @property integer|null $input
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|self[] $children
 * @property-read int|null $children_count
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Category|null $parent
 * @property-read Product|null $product
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read CategoryTranslation|null $translation
 * @property-read Collection|CategoryTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static CategoryFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self withThreeChildren($filter)
 * @method static Builder|self withSecondChildren($filter)
 * @method static Builder|self withParent($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereWork($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereKeywords($value)
 * @method static Builder|self whereParentId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Category extends Model
{
    use HasFactory, Loadable, MetaTagable;

    protected $guarded = ['id'];

    const MAIN      = 1;
    const SUB_MAIN  = 2;
    const CHILD     = 3;
    const CAREER    = 10;

    const TYPES = [
        'main'      => self::MAIN,
        'sub_main'  => self::SUB_MAIN,
        'child'     => self::CHILD,
        'career'    => self::CAREER,
    ];

    const TYPES_VALUES = [
        self::MAIN      => 'main',
        self::SUB_MAIN  => 'sub_main',
        self::CHILD     => 'child',
        self::CAREER    => 'career',
    ];

    const PENDING     = 'pending';
    const PUBLISHED   = 'published';
    const UNPUBLISHED = 'unpublished';

    protected $casts = [
        'work'       => 'bool',
        'active'     => 'bool',
        'type'       => 'int',
        'input'      => 'int',
        'age_limit'  => 'int',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CategoryTranslation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

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
        $query->where('updated_at', '>', $updatedDate);
    }

    #region Withes

    public function scopeWithSecondChildren($query, $data)
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $query->with([
            'parent.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'children' => fn($q) => $q->when(request()->is('api/v1/rest/*'), function ($q) {
                    $q->where('active', true);
                },
                fn($q) => $q->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
            ),

            'children.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),
        ]);
    }

    public function scopeWithParent($query, $data)
    {
        $query->with([
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'parent' => fn($q) => $q->when(request()->is('api/v1/rest/*'), function ($q) {
                $q->where('active', true);
            },
                fn($q) => $q->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
            ),

            'parent.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),
        ]);
    }

    public function scopeWithThreeChildren($query, $data)
    {
        $query->with([
            'parent.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'translation' => fn($q) => $q->where('locale', data_get($data, 'lang')),

            'children' => fn($q) => $q
                ->when(request()->is('api/v1/rest/*'), function ($q) {
                    $q->where('active', true);
                },
                    fn($q) => $q->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
                ),

            'children.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'children.children' => fn($q) => $q
                ->when(request()->is('api/v1/rest/*'), function ($q) {
                    $q->where('active', true);
                },
                    fn($q) => $q->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
                ),

            'children.children.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),

            'children.children.children' => fn($q) => $q
                ->when(request()->is('api/v1/rest/*'), function ($q) {
                    $q->where('active', true);
                },
                    fn($q) => $q->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
                ),

            'children.children.children.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'lang')),
        ]);
    }

    #endregion

    /* Filter Scope */
    public function scopeFilter($value, $filter)
    {
        return $value
            ->when(request()->is('api/v1/rest/*'), fn($q) => $q->where('active', true))
            ->when(data_get($filter, 'slug'), fn($q, $slug) => $q->where('slug', $slug))
            ->when(in_array(data_get($filter, 'type'), array_keys(Category::TYPES)), function ($q) use ($filter) {
                $q->where('type', '=', data_get(Category::TYPES, $filter['type'],Category::MAIN));
            })
            ->when(isset($filter['active']), function ($q) use ($filter) {
                $q->whereActive($filter['active']);
            })
            ->when(isset($filter['work']), function ($q) use ($filter) {
                $q->whereWork($filter['work']);
            })
            ->when(data_get($filter, 'parent_id'), function ($q, $parentId) {
                $q->where('parent_id', $parentId);
            })
            ->when(data_get($filter, 'parent_ids'), function ($q, $parentIds) {
                $q->whereIn('parent_id', $parentIds);
            })
            ->when(data_get($filter, 'has_products'), fn(Builder $b) => $b->where(function ($b) {

                if (request('type') === self::TYPES_VALUES[self::MAIN]) {
                    $b
                        ->whereHas('product', function ($q) {
                            $q->where('active', true);
                        })
                        ->orWhereHas('children.product', function ($q) {
                            $q->where('active', true);
                        })
                        ->orWhereHas('children.children.product', function ($q) {
                            $q->where('active', true);
                        });
                }

                if (request('type') === self::TYPES_VALUES[self::SUB_MAIN]) {
                    $b
                        ->whereHas('product', function ($q) {
                            $q->where('active', true);
                        })
                        ->orWhereHas('children.product', function ($q) {
                            $q->where('active', true);
                        });
                }

                if (request('type') === self::TYPES_VALUES[self::CHILD]) {
                    $b->whereHas('product', function ($q) {
                        $q->where('active', true);
                    });
                }

            }))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->where(function ($q) use($search) {
                    $q->where('keywords', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');

                        })->orWhereHas('children.translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');

                        })->orWhereHas('children.children.translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');
                        });
                });
            })
            ->when(data_get($filter, 'age_from'), function ($q, $ageFrom) use ($filter) {
                $q
                    ->where('age_limit', '>=', $ageFrom)
                    ->where('age_limit', '<=', data_get($filter, 'age_to', 1000000));
            })
            ->when(data_get($filter, 'age_limit'), fn($q, $ageLimit) => $q->where('age_limit', $ageLimit));
    }
}
