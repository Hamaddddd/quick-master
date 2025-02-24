<?php
declare(strict_types=1);

namespace App\Services\AttributeService;

use App\Helpers\ResponseError;
use App\Models\Attribute;
use App\Models\AttributeValueTranslation;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

final class AttributeService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Attribute::class;
    }

    public function create(array $data): array
    {
        try {
            /** @var Attribute $model */
            $model = $this->model()->create($data);

            if (isset($data['value'])) {
                foreach ($data['value'] as $value) {
                    $attributeValue = $model->attributeValues()->create($value);
                    $this->setTranslations($attributeValue, ['title' => $value]);
                }
            }

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function createMany(array $data): array
    {
        try {
            $models = DB::transaction(function () use ($data) {

                $models = [];

                foreach ($data['data'] as $item) {

                    /** @var Attribute $model */
                    $model = $this->model()->create($item);

                    if (isset($item['value'])) {
                        foreach ($item['value'] as $value) {

                            $value['attribute_id'] = $model->id;

                            $attributeValue = $model->attributeValues()
                                ->when(
                                    isset($value['id']),
                                    fn($q) => $q->updateOrCreate(['id' => $value['id']], $value),
                                    fn($q) => $q->create($value),
                                );
                            $this->setTranslations($attributeValue, ['title' => $value]);
                        }
                    }

                    $this->setTranslations($model, $item);

                    $models[] = $model;
                }

                return $models;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $models];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(Attribute $model, $data): array
    {
        try {
            $model->update($data);

            if (isset($data['delete_value_ids'])) {
                DB::table('attribute_value_translations')
                    ->whereIn('attribute_value_id', $data['delete_value_ids'])
                    ->delete();

                DB::table('attribute_values')
                    ->where('attribute_id', $model->id)
                    ->whereIn('id', $data['delete_value_ids'])
                    ->delete();
            }

            if (isset($data['value'])) {

                foreach ($data['value'] as $value) {

                    $value['attribute_id'] = $model->id;

                    $attributeValue = $model->attributeValues()
                        ->when(
                            isset($value['id']),
                            fn($q) => $q->updateOrCreate(['id' => $value['id']], $value),
                            fn($q) => $q->create($value),
                        );

                    unset($value['id']);

                    $this->setTranslations($attributeValue, ['title' => $value]);

                }

            }

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (Attribute::whereIn('id', (array)$ids)->get() as $model) {
            /** @var Attribute $model */
            $model->translations()->delete();
            $model->attributeValues()->delete();
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
