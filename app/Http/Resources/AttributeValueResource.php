<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var AttributeValue|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'           => $this->when($this->id, $this->id),
            'translation'  => TranslationResource::make($this->whenLoaded('translation')),
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'      => $this->when($locales, $locales),
            'attribute'    => AttributeResource::make($this->whenLoaded('attribute')),
            'products'     => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
