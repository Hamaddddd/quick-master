<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Blog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Str;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Review|JsonResource $this */

        $assignable = $this->whenLoaded('assignable');

        return [
            'id'                => $this->id,
            'reviewable_id'     => $this->when($this->reviewable_id, $this->reviewable_id),
            'reviewable_type'   => $this->when($this->reviewable_type, Str::after($this->reviewable_type,'s\\')),
            'assignable_id'     => $this->when($this->assignable_id, $this->assignable_id),
            'assignable_type'   => $this->when($this->assignable_type, Str::after($this->assignable_type,'s\\')),
            'rating'            => $this->rating,
            'comment'           => $this->comment,
            'img'               => $this->img,
            'ordered'           => (bool)request('ordered'),
            'added_review'      => (bool)request('added_review'),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'galleries' => GalleryResource::collection($this->whenLoaded('galleries')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'order' => $this->when(
                $this->reviewable_type === Order::class,
                OrderResource::make($this->whenLoaded('reviewable'))
            ),
            'product' => $this->when(
                $this->reviewable_type === Product::class,
                ProductResource::make($this->whenLoaded('reviewable'))
            ),
            'blog' => $this->when(
                $this->reviewable_type === Blog::class,
                BlogResource::make($this->whenLoaded('reviewable'))
            ),
           ];
    }
}
