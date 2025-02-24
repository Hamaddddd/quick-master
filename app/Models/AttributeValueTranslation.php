<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\AttributeValueTranslation
 *
 * @property int $id
 * @property int $attribute_value_id
 * @property string $locale
 * @property string $title
 * @property AttributeValue|null $attributeValue
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AttributeValueTranslation extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
