<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AttributeTranslation
 *
 * @property int $id
 * @property int $attribute_id
 * @property string $locale
 * @property string $title
 * @property string|null $placeholder
 * @property string|null $placeholder_to
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AttributeTranslation extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;
}
