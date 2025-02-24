<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Order|JsonResource $this */
        return [
            'id'                         => $this->when($this->id, $this->id),
            'user_id'                    => $this->when($this->user_id, $this->user_id),
            'total_price'                => $this->when($this->rate_total_price, $this->rate_total_price),
            'rate'                       => $this->when($this->rate, $this->rate),
            'created_at'                 => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'user'                       => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
