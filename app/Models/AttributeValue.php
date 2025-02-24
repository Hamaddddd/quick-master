<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\AttributeValue
 *
 * @property int $id
 * @property int $attribute_id
 * @property-read Attribute|null $attribute
 * @property-read AttributeValueTranslation|null $translation
 * @property-read Collection|AttributeValueProduct[] $attributeValueProducts
 * @property-read Collection|AttributeValueTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AttributeValue extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    // Products
    public function attributeValueProducts(): HasMany
    {
        return $this->hasMany(AttributeValueProduct::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(AttributeValueTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(AttributeValueTranslation::class);
    }
}
