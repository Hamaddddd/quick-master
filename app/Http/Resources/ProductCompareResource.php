<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCompareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Product|JsonResource $this */
        return [
            'id'            => $this->when($this->id,           $this->id),
            'slug'          => $this->when($this->slug,         $this->slug),
            'category_id'   => $this->when($this->category_id,  $this->category_id),
            'keywords'      => $this->when($this->keywords,     $this->keywords),
            'brand_id'      => $this->when($this->brand_id,     $this->brand_id),
            'status'        => $this->when($this->status,       $this->status),
            'status_note'   => $this->when($this->status_note,  $this->status_note),
            'img'           => $this->when($this->img,          $this->img),
            'price'         => $this->rate_price ?? 0,
            'active'        => (bool) $this->active,
            'visibility'    => (bool) $this->visibility,
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'translation'   => TranslationResource::make($this->translation),
            'category'      => CategoryResource::make($this->category),
            'brand'         => BrandResource::make($this->brand),
        ];
    }

}
