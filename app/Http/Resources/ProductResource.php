<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Addons\AdsPackage\Resources\ProductAdsPackageResource;
use App\Models\Attribute;
use App\Models\AttributeValueProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        $attributes = [];

        if (data_get($this, 'get_attributes')) {

            foreach (AttributeValueProduct::with([
                'attributeValue.translation' => fn($q) => $q->where('locale', request('lang', 'en'))
            ])->where('product_id', $this->id)->get() as $attributeValueProduct) {

                /** @var AttributeValueProduct $attributeValueProduct */
                if (!isset($attributes[$attributeValueProduct->attribute_id])) {

                    $attribute = Attribute::with([
                        'translation' => fn($q) => $q->where('locale', request('lang', 'en'))
                    ])
                        ->where('id', $attributeValueProduct->attribute_id)
                        ->first();

                    if (empty($attribute)) {
                        continue;
                    }

                    $attributes[$attributeValueProduct->attribute_id] = $attribute->toArray();

                }

                $attributes[$attributeValueProduct->attribute_id]['values'][] = $attributeValueProduct->toArray();

            }

        }

        return [
            'id'                  => $this->when($this->id,           $this->id),
            'slug'                => $this->when($this->slug,         $this->slug),
            'user_id'             => $this->when($this->user_id,      $this->user_id),
            'region_id'           => $this->when($this->region_id,    $this->region_id),
            'country_id'          => $this->when($this->country_id,   $this->country_id),
            'city_id'             => $this->when($this->city_id,      $this->city_id),
            'area_id'             => $this->when($this->area_id,      $this->area_id),
            'category_id'         => $this->when($this->category_id,  $this->category_id),
            'keywords'            => $this->when($this->keywords,     $this->keywords),
            'brand_id'            => $this->when($this->brand_id,     $this->brand_id),
            'status'              => $this->when($this->status,       $this->status),
            'status_note'         => $this->when($this->status_note,  $this->status_note),
            'type'                => $this->when($this->type,         $this->type),
            'img'                 => $this->when($this->img,          $this->img),
            'phone'               => $this->when($this->phone,        $this->phone),
            'price'               => $this->rate_price ?? 0,
            'price_from'          => $this->rate_price_from ?? 0,
            'price_to'            => $this->rate_price_to ?? 0,
            'active'              => $this->active,
            'work'                => $this->work,
            'visibility'          => $this->visibility,
            'state'               => $this->state,
            'contact_name'        => $this->contact_name,
            'email'               => $this->email,
            'likes_count'         => $this->likes_count,
            'phone_views_count'   => $this->phone_views_count,
            'message_click_count' => $this->message_click_count,
            'views_count'         => $this->views_count,
            'created_at'          => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'          => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'attributes'          => collect($attributes)->values()->toArray(),
            'translation'         => TranslationResource::make($this->whenLoaded('translation')),
            'translations'        => TranslationResource::collection($this->whenLoaded('translations')),
            'product_ads_package' => ProductAdsPackageResource::make($this->whenLoaded('productAdsPackage')),
            'locales'             => $this->when($locales, $locales),
            'stories'             => SimpleStoryResource::collection($this->whenLoaded('stories')),
            'category'            => CategoryResource::make($this->whenLoaded('category')),
            'brand'               => BrandResource::make($this->whenLoaded('brand')),
            'unit'                => UnitResource::make($this->whenLoaded('unit')),
            'reviews'             => ReviewResource::collection($this->whenLoaded('reviews')),
            'galleries'           => GalleryResource::collection($this->galleries),
            'tags'                => TagResource::collection($this->whenLoaded('tags')),
            'meta_tags'           => MetaTagResource::collection($this->whenLoaded('metaTags')),
            'region'              => RegionResource::make($this->whenLoaded('region')),
            'country'             => CountryResource::make($this->whenLoaded('country')),
            'city'                => CityResource::make($this->whenLoaded('city')),
            'area'                => AreaResource::make($this->whenLoaded('area')),
            'user'                => UserResource::make($this->whenLoaded('user')),
            'work_requests'       => WorkRequestResource::make($this->whenLoaded('workRequests')),
        ];
    }

}
