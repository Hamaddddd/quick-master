<?php
declare(strict_types=1);

namespace App\Services\WorkRequestService;

use App\Helpers\ResponseError;
use App\Models\Product;
use App\Models\UserResume;
use App\Models\WorkRequest;
use App\Services\CoreService;
use Exception;

final class WorkRequestService extends CoreService
{
    protected function getModelClass(): string
    {
        return WorkRequest::class;
    }

    public function create(array $data): array
    {
        try {
            $data['owner_id'] = Product::select(['user_id'])->find($data['product_id'])?->user_id;

            $exists = WorkRequest::where('user_id', auth('sanctum')->id())
                ->where('product_id', $data['product_id'])
                ->exists();

            if ($exists) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_118,
                    'message' => __('errors.' . ResponseError::ERROR_118, locale: $this->language)
                ];
            }

            /** @var WorkRequest $model */
            $model = $this->model()->create($data);

            $this->afterSave($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(WorkRequest $model, array $data): array
    {
        try {
            $data['owner_id'] = Product::select(['user_id'])->find($data['product_id'])?->user_id;

            $model->update($data);

            $this->afterSave($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param WorkRequest $model
     * @param array $data
     * @return void
     */
    public function afterSave(WorkRequest $model, array $data): void
    {
        if (@$data['save_resume']) {
            $userResume = UserResume::updateOrCreate(['user_id' => $data['user_id']], $data);
        }

        if (!data_get($data, 'images.0')) {
            return;
        }

        $model->uploads(data_get($data, 'images'));

        if (isset($userResume)) {
            $userResume->uploads(data_get($data, 'images'));
        }
    }

    public function delete(?array $ids = [], ?int $userId = null): array
    {
        $models = WorkRequest::whereIn('id', (array)$ids)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get();

        foreach ($models as $model) {
            /** @var WorkRequest $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
