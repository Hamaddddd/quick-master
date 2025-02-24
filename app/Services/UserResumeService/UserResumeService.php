<?php
declare(strict_types=1);

namespace App\Services\UserResumeService;

use App\Helpers\ResponseError;
use App\Models\UserResume;
use App\Services\CoreService;
use Exception;

final class UserResumeService extends CoreService
{
    protected function getModelClass(): string
    {
        return UserResume::class;
    }

    public function create(array $data): array
    {
        try {
            $model = UserResume::updateOrCreate(['user_id' => $data['user_id']], $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], ?int $userId = null): array
    {
        $models = UserResume::whereIn('id', (array)$ids)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get();

        foreach ($models as $model) {
            /** @var UserResume $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
