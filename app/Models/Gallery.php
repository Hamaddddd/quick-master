<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Schema;

/**
 * App\Models\Gallery
 *
 * @property int $id
 * @property string $title
 * @property string $loadable_type
 * @property int $loadable_id
 * @property string|null $type
 * @property string|null $path
 * @property string|null $mime
 * @property string|null $preview
 * @property string|null $size
 * @property string|null $isset
 * @property-read Model|Eloquent $loadable
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLoadableId($value)
 * @method static Builder|self whereLoadableType($value)
 * @method static Builder|self whereMime($value)
 * @method static Builder|self wherePath($value)
 * @method static Builder|self whereSize($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereType($value)
 * @mixin Eloquent
 */
class Gallery extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    const BANNERS               = 'banners';
    const BRANDS                = 'brands';
    const BLOGS                 = 'blogs';
    const CATEGORIES            = 'categories';
    const REVIEWS               = 'reviews';
    const CHATS                 = 'chats';
    const PRODUCTS              = 'products';
    const LANGUAGES             = 'languages';
    const REFERRAL              = 'referral';
    const USERS                 = 'users';
    const ADS_PACKAGE           = 'ads-package';
    const COUNTRIES             = 'countries';
    const WORK_REQUESTS         = 'work_requests';
    const OTHER                 = 'other';

    const TYPES = [
        self::BANNERS           => self::BANNERS,
        self::BRANDS            => self::BRANDS,
        self::BLOGS             => self::BLOGS,
        self::CATEGORIES        => self::CATEGORIES,
        self::REVIEWS           => self::REVIEWS,
        self::CHATS             => self::CHATS,
        self::PRODUCTS          => self::PRODUCTS,
        self::LANGUAGES         => self::LANGUAGES,
        self::REFERRAL          => self::REFERRAL,
        self::USERS             => self::USERS,
        self::ADS_PACKAGE       => self::ADS_PACKAGE,
        self::COUNTRIES         => self::COUNTRIES,
        self::WORK_REQUESTS     => self::WORK_REQUESTS,
        self::OTHER             => self::OTHER,
    ];

    public function loadable(): MorphTo
    {
        return $this->morphTo('loadable');
    }

    public function scopeFilter(Builder $query, array $filter) {

        $type   = data_get($filter, 'type', self::OTHER);
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('galleries', $column) ? $column : 'id';
        }

        $query
            ->where('type', $type)

            ->orderBy($column, data_get($filter, 'sort', 'desc'));
    }
}
