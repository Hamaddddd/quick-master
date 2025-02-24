<?php
declare(strict_types=1);

namespace App\Models;

use App\Addons\AdsPackage\Models\ProductAdsPackage;
use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Likable;
use App\Traits\Loadable;
use App\Traits\MetaTagable;
use App\Traits\Regions;
use App\Traits\Reviewable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Schema;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $slug
 * @property int $category_id
 * @property int $price
 * @property int $price_from
 * @property int $price_to
 * @property int $brand_id
 * @property int $user_id
 * @property int|null $unit_id
 * @property bool $visibility
 * @property int|null $region_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $area_id
 * @property string|null $keywords
 * @property string|null $img
 * @property boolean|null $work
 * @property boolean|null $state
 * @property boolean|null $contact_name
 * @property boolean|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property boolean $active
 * @property string $status
 * @property string $status_note
 * @property string $phone
 * @property string $type
 * @property-read double $rate_price
 * @property-read double $rate_price_from
 * @property-read double $rate_price_to
 * @property-read Brand $brand
 * @property-read User|null $user
 * @property-read Collection|Tag[] $tags
 * @property-read Category $category
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Story[] $stories
 * @property-read int|null $stories_count
 * @property-read int|null $product_sales_count
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read ProductTranslation|null $translation
 * @property-read Collection|ProductTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|AttributeValueProduct[] $attributeValueProducts
 * @property-read int|null $attribute_value_products_count
 * @property-read int|null $likes_count
 * @property-read int $views_count
 * @property-read int $phone_views_count
 * @property-read int $message_click_count
 * @property-read Unit|null $unit
 * @property-read Collection|WorkRequest[] $workRequests
 * @property-read int|null $work_requests_count
 * @method static Builder|self filter($filter)
 * @method static Builder|self active($active)
 * @method static Builder|self actual($lang)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBrandId($value)
 * @method static Builder|self whereCategoryId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereKeywords($value)
 * @method static Builder|self whereUnitId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Product extends Model
{
    use HasFactory, Loadable, Reviewable, Likable, SetCurrency, MetaTagable, SoftDeletes, Regions, Countries, Cities, Areas;

    protected $guarded = ['id'];

    protected $casts = [
        'active'     => 'boolean',
        'interval'   => 'double',
        'visibility' => 'boolean',
    ];

    const PUBLISHED     = 'published';
    const PENDING       = 'pending';
    const UNPUBLISHED   = 'unpublished';

    const STATUSES = [
        self::PUBLISHED   => self::PUBLISHED,
        self::PENDING     => self::PENDING,
        self::UNPUBLISHED => self::UNPUBLISHED,
    ];

    const PRIVATE  = 'private';
    const BUSINESS = 'business';

    const TYPES = [
        self::PRIVATE  => self::PRIVATE,
        self::BUSINESS => self::BUSINESS
    ];

    const USED = 0;
    const NEW  = 1;

    const STATES = [
        self::USED => self::USED,
        self::NEW  => self::NEW
    ];

    public function getRatePriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * ($this->currency() <= 0 ? 1 : $this->currency());
        }

        return $this->price;
    }

    public function getRatePriceFromAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price_from * ($this->currency() <= 0 ? 1 : $this->currency());
        }

        return $this->price_from;
    }

    public function getRatePriceToAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price_to * ($this->currency() <= 0 ? 1 : $this->currency());
        }

        return $this->price_to;
    }

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ProductTranslation::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    // Product Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Product Brand
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannerProduct(): HasOne
    {
        return $this->hasOne(BannerProduct::class);
    }

    public function productAdsPackage(): HasOne
    {
        return $this->hasOne(ProductAdsPackage::class);
    }

    public function attributeValueProducts(): HasMany
    {
        return $this->hasMany(AttributeValueProduct::class);
    }

    public function workRequests(): HasMany
    {
        return $this->hasMany(WorkRequest::class);
    }

    public function scopeActual($query, $lang)
    {
        return $query->where(function ($q) use ($lang) {
            $q
                ->whereHas('translation', fn($q) => $q->where('locale', $lang))
                ->where('active', true)
                ->where('status', Product::PUBLISHED);
        });
    }

    public function scopeFilter($query, array $filter): void
    {
        $column  = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        $regionId   = $filter['region_id']  ?? null;
        $countryId  = $filter['country_id'] ?? null;
        $cityId     = $filter['city_id']    ?? null;
        $areaId     = $filter['area_id']    ?? null;

        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $notRest = !request()->is('api/v1/rest/*');
        $notLike = !request()->is('api/v1/dashboard/likes');

        $query
            ->when(data_get($filter, 'slug'), fn($q, $slug) => $q->where('slug', $slug))
            ->when(isset($filter['banner_id']), function ($q) use ($filter) {
                $q->whereHas('bannerProduct', fn($q) => $q->where('banner_id', $filter['banner_id']));
            })
            ->when(isset($filter['not_in']), function ($q) use ($filter) {
                $q->whereNotIn('id', $filter['not_in']);
            })
            ->when(isset($filter['category_id']), function ($q) use ($filter) {

                /** @var Category|null $category */
                $category = Category::with('children:id,parent_id')
                    ->select(['id'])
                    ->firstWhere('id', $filter['category_id']);

                $ids   = $category?->children?->pluck('id')?->toArray();
                $ids[] = $category?->id;

                $q->whereIn('category_id', $ids);
            })
            ->when(isset($filter['visibility']), function ($q) use ($filter) {
                $q->where('visibility', $filter['visibility']);
            })
            ->when(isset($filter['work']), function ($q) use ($filter) {
                $q->whereWork($filter['work']);
            })
            ->when(isset($filter['user_id']), function ($q) use ($filter) {
                $q->where('user_id', $filter['user_id']);
            })
            ->when(isset($filter['attributes']), function ($query) use ($filter) {
                $query->whereHas('attributeValueProducts', function ($q) use ($filter) {

                    $attributes = collect($filter['attributes']);
                    $valueIds   = $attributes->pluck('value_id')->toArray();
                    $values     = $attributes->pluck('value')->toArray();

                    if (count($valueIds) > 0) {
                        $q->whereIn('attribute_value_id', $valueIds);
                    }

                    if (count($values) > 0) {

                        foreach ($values as $value) {

                            $from = $value['from'] ?? null;
                            $to   = $value['to'] ?? null;

                            if (empty($from) && empty($to)) {

                                $q->where('value', $value);

                                continue;
                            }

                            $q
                                ->when($from, fn($q) => $q->where('value', '>=', $from))
                                ->when($to,   fn($q) => $q->where('value', '<=', $to));

                        }

                    }

                });
            })
            ->when($byLocation, function ($q) use ($notRest, $notLike, $regionId, $countryId, $cityId, $areaId) {
                $inDashboard = $notRest && $notLike;

                $q
                    ->where('region_id', $regionId)
                    ->when(!empty($countryId), fn($q) => $q->where('country_id', $countryId), !empty($regionId)  && $inDashboard ? fn($q) => $q->whereNull('country_id') : fn($q) => $q)
                    ->when(!empty($cityId),    fn($q) => $q->where('city_id', $cityId),       !empty($countryId) && $inDashboard ? fn($q) => $q->whereNull('city_id')    : fn($q) => $q)
                    ->when(!empty($areaId),    fn($q) => $q->where('area_id', $areaId),       !empty($areaId)    && $inDashboard ? fn($q) => $q->whereNull('area_id')    : fn($q) => $q);
            })
            ->when(isset($filter['status']), function ($q) use ($filter) {
                $q->where('status', $filter['status']);
            })
            ->when(isset($filter['brand_id']), function ($q) use ($filter) {
                $q->where('brand_id', $filter['brand_id']);
            })
            ->when(data_get($filter, 'category_ids'), function ($q, $ids) {

                $categoryIds = DB::table('categories')
                    ->whereIn('id', $ids)
                    ->orWhereIn('parent_id', $ids)
                    ->select(['id', 'parent_id'])
                    ->pluck('id')
                    ->toArray();

                $q->whereIn('category_id', $categoryIds);
            })
            ->when(data_get($filter, 'brand_ids.*'), function ($q, $brandIds) {
                $q->whereIn('brand_id', $brandIds);
            })
            ->when(isset($filter['price_from']), function ($q) use ($filter) {

                $priceFrom = data_get($filter, 'price_from') / $this->currency();
                $priceTo   = data_get($filter, 'price_to', Product::max('price')) / $this->currency();

                $q->where('price', '>=', $priceFrom)->where('price', '<=', $priceTo);

            })
            ->when(isset($filter['active']), fn($query) => $query->where('active', $filter['active']))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d 00:00:01', strtotime($dateFrom));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo . ' +1 day'));

                $query->where([
                    ['created_at', '>=', $dateFrom],
                    ['created_at', '<=', $dateTo],
                ]);
            })
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query
                        ->where('keywords', 'LIKE', "%$search%")
                        ->orWhere('id', $search)
                        ->orWhere('slug', 'LIKE', "%$search%")
                        ->orWhereHas('translation', function ($q) use($search) {
                            $q->where('title', 'LIKE', "%$search%")
                                ->select('id', 'product_id', 'locale', 'title');
                        });
                });
            })
            ->when(
                data_get($filter, 'order_by'),
                function (Builder $query, $orderBy) {

                switch ($orderBy) {
                    case 'new':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'old':
                        $query->orderBy('created_at');
                        break;
                    case 'best_sale':
                        $query->orderBy('od_count', 'desc');
                        break;
                    case 'low_sale':
                        $query->orderBy('od_count');
                        break;
                    case 'high_rating':
                        $query->orderBy('r_avg', 'desc');
                        break;
                    case 'low_rating':
                        $query->orderBy('r_avg');
                        break;
                }

            },
                fn($q) => $q->orderBy($column, data_get($filter, 'sort', 'desc'))
            );
    }
}
