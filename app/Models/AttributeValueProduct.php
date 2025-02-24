<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\AttributeValue
 *
 * @property int $product_id
 * @property int $attribute_id
 * @property int $attribute_value_id
 * @property string $value
 * @property-read Product|null $product
 * @property-read Attribute|null $attribute
 * @property-read AttributeValue|null $attributeValue
 * @property-read AttributeTranslation|null $attributeTranslation
 * @property-read AttributeValueTranslation|null $attributeValueTranslation
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AttributeValueProduct extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function attributeTranslation(): HasOne
    {
        return $this->hasOne(AttributeTranslation::class, 'attribute_id', 'attribute_id');
    }

    public function attributeValueTranslation(): HasOne
    {
        return $this->hasOne(AttributeValueTranslation::class, 'attribute_value_id', 'attribute_value_id');
    }
}
