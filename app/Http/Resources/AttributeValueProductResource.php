<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttributeValueProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var AttributeValueProduct|JsonResource $this */
        return [
            'product_id'         => $this->when($this->product_id, $this->product_id),
            'attribute_id'       => $this->when($this->attribute_id, $this->attribute_id),
            'attribute_value_id' => $this->when($this->attribute_value_id, $this->attribute_value_id),
            'value'              => $this->when($this->value, $this->value),
            'product'            => ProductResource::make($this->whenLoaded('product')),
            'attribute'          => AttributeResource::make($this->whenLoaded('attribute')),
            'attributeValue'     => AttributeValueResource::make($this->whenLoaded('attributeValue')),
        ];
    }

}
