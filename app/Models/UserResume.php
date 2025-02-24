<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Schema;

/**
 * App\Models\UserResume
 *
 * @property int $id
 * @property int $first_name
 * @property int $last_name
 * @property int $phone
 * @property int $email
 * @property int $resume
 * @property int $message
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserResume extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $column  = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('user_resumes', $column) ? $column : 'id';
        }

        $query
            ->when(isset($filter['user_id']), function ($q) use ($filter) {
                $q->where('user_id', $filter['user_id']);
            })
            ->orderBy($column, data_get($filter, 'sort', 'desc'));
    }
}
