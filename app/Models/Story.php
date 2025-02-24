<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Story
 *
 * @property int $id
 * @property array $file_urls
 * @property int $product_id
 * @property boolean $active
 * @property string $color
 * @property Product|null $product
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|StoryTranslation[] $translations
 * @property-read StoryTranslation|null $translation
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereProductId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Story extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'color'      => 'string',
        'active'     => 'boolean',
        'file_urls'  => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function translation(): HasOne
    {
        return $this->hasOne(StoryTranslation::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(StoryTranslation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
