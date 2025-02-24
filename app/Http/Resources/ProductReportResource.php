<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReportResource extends JsonResource
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
            'id'            => $this->id,
            'category_id'   => $this->category_id,
            'active'        => $this->active,
            'price'         => $this->rate_price,
            // Relations
            'translation'   => TranslationResource::make($this->translation),
            'category'      => CategoryResource::make($this->category),
        ];
    }

}
