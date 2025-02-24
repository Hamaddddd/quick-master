<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Story|JsonResource $this */
        return [
            'id'            => $this->when($this->id, $this->id),
            'color'         => $this->when($this->color, $this->color),
            'active'        => $this->when($this->active, $this->active),
            'file_urls'     => $this->when($this->file_urls, $this->file_urls),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'product'       => ProductResource::make($this->whenLoaded('product')),
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
