<?php
declare(strict_types=1);

namespace App\Repositories\UserResumeRepository;

use App\Models\UserResume;
use App\Repositories\CoreRepository;

class UserResumeRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return UserResume::class;
    }

    /**
     * @param int $id
     * @return UserResume|null
     */
    public function show(int $id): ?UserResume
    {
        return UserResume::find($id);
    }

}
