<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StoryTranslation
 *
 * @property int $id
 * @property int $story_id
 * @property string $locale
 * @property string $title
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @mixin Eloquent
 */
class StoryTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}
