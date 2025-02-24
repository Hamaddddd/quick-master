<?php
declare(strict_types=1);

namespace App\Services\StoryService;

use App\Helpers\ResponseError;
use App\Models\Settings;
use App\Models\Story;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Illuminate\Http\UploadedFile;
use Throwable;

class StoryService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Story::class;
    }

    public function create(array $data): array
    {
        try {
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => [],
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }

    public function update(Story $story, array $data): array
    {
        try {
            $story->update($data);

            $this->setTranslations($story, $data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => [],
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }

    public function delete(?array $ids = []): array
    {
        $stories = Story::whereIn('id', (array)$ids)->get();

        foreach ($stories as $story) {
            /** @var Story $story */
            $this->removeFiles((array)$story->file_urls);
            $story->delete();
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function uploadFiles(array $data): array
    {
        $fileUrls = [];

        $isAws = Settings::where('key', 'aws')->first();

        $options = [];

        $dir = 'storage';

        if (data_get($isAws, 'value')) {
            $options = ['disk' => 's3'];
            $dir = 'public';
        }

        foreach (data_get($data, 'files') as $file) {

            try {
                /** @var UploadedFile $file */
                $id = auth('sanctum')->id() ?? "0001";

                $ext = strtolower(
                    preg_replace("#.+\.([a-z]+)$#i", "$1", str_replace(['.png', '.jpg'], '.webp', $file->getClientOriginalName()))
                );

                $fileName = $id . '-' . now()->unix() . '.' . $ext;

                $url = $file->storeAs("public/stories", $fileName, $options);

                $url = str_replace('public/stories/', "$dir/stories/", $url);

                $fileUrls[] = str_replace("$dir/images/", '', config('app.img_host')) . $url;

            } catch (Throwable $e) {
                $message = $e->getMessage();

                if ($message === "Class \"finfo\" not found") {
                    $message = __('errors.' . ResponseError::FIN_FO, locale: $this->language);
                }

                return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $message];
            }

        }

        if (count($fileUrls) === 0) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_400,
            ];
        }

        return [
            'status'    => true,
            'code'      => ResponseError::NO_ERROR,
            'data'      => $fileUrls,
        ];
    }

    public function removeFiles(array $fileUrls) {

        foreach ($fileUrls as $fileUrl) {
            try {
                $storageUrl = str_replace(request()->getHttpHost() . '/storage', 'app/public', $fileUrl);
                unlink(storage_path($storageUrl));
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

    /**
     * @param int $id
     * @return array
     */
    public function changeActive(int $id): array
    {
        $story = Story::find($id);

        if (empty($story)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ];
        }

        $story->update([
            'active' => !$story->active,
        ]);

        return [
            'status'  => true,
            'code'    => ResponseError::NO_ERROR,
            'message' => __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
        ];
    }

}
