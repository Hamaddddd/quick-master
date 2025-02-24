<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var WorkRequest|JsonResource $this */
        return [
            'id'            => $this->when($this->id,         $this->id),
            'first_name'    => $this->when($this->first_name, $this->first_name),
            'last_name'     => $this->when($this->last_name,  $this->last_name),
            'phone'         => $this->when($this->phone,      $this->phone),
            'email'         => $this->when($this->email,      $this->email),
            'resume'        => $this->when($this->resume,     $this->resume),
            'message'       => $this->when($this->message,    $this->message),
            'product_id'    => $this->when($this->product_id, $this->product_id),
            'user_id'       => $this->when($this->user_id,    $this->user_id),
            'owner_id'      => $this->when($this->owner_id,   $this->owner_id),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            /* Relations */
            'product'       => ProductResource::make($this->whenLoaded('product')),
            'user'          => UserResource::make($this->whenLoaded('user')),
            'owner'         => UserResource::make($this->whenLoaded('owner')),
        ];
    }

}
