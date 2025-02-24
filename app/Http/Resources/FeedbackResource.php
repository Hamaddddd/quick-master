<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Feedback|JsonResource $this*/
        return [
            'id'         => $this->when($this->id,          $this->id),
            'product_id' => $this->when($this->product_id,  $this->product_id),
            'helpful'    => (bool)$this->helpful,
            'created_at' => $this->when($this->created_at,  $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at' => $this->when($this->updated_at,  $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'user'       => UserResource::make($this->whenLoaded('user')),
            'product'    => ProductResource::make($this->whenLoaded('product')),
        ];
    }
}
