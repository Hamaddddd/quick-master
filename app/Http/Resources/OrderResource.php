<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'id'                            => $this->when($this->id, $this->id),
            'user_id'                       => $this->when($this->user_id, $this->user_id),
            'total_price'                   => $this->when($this->rate_total_price, $this->rate_total_price),
            'rate'                          => $this->when($this->rate, $this->rate),
            'location'                      => $this->when($this->location, $this->location),
            'address'                       => $this->when($this->address, $this->address),
            'phone'                         => $this->when($this->phone, $this->phone),
            'username'                      => $this->when($this->username, $this->username),
            'img'                           => $this->when($this->img, $this->img),
            'created_at'                    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'                    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'currency'                      => CurrencyResource::make($this->whenLoaded('currency')),
            'user'                          => UserResource::make($this->whenLoaded('user')),
            'transaction'                   => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'                  => TransactionResource::collection($this->whenLoaded('transactions')),
            'review'                        => ReviewResource::make($this->whenLoaded('review')),
            'reviews'                       => ReviewResource::collection($this->whenLoaded('reviews')),
            'logs'                          => ModelLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
