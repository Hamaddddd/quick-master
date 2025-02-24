<?php
declare(strict_types=1);

namespace App\Http\Resources;

use DB;
use App\Models\User;
use App\Helpers\Utility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var User|JsonResource $this */

        $reviewsGroup = null;

        if ($request->input('ReviewCountGroup')) {
            $reviewsGroup = $this->prepareReviewCountGroup($this->id);
        }

        $role = $this->relationLoaded('roles') ? $this->role : null;

        return [
            'id'                => $this->when($this->id, $this->id),
            'uuid'              => $this->when($this->uuid, $this->uuid),
            'firstname'         => $this->when($this->firstname, $this->firstname),
            'lastname'          => $this->when($this->lastname, $this->lastname),
            'empty_p'           => empty($this->password),
            'email'             => $this->when($this->email, $this->email),
            'phone'             => $this->when($this->phone, $this->phone),
            'birthday'          => $this->when($this->birthday, $this->birthday?->format('Y-m-d H:i:s') . 'Z'),
            'gender'            => $this->when($this->gender, $this->gender),
            'active'            => (boolean)$this->active,
            'img'               => $this->when($this->img, $this->img),
            'referral'          => $this->when($this->referral, $this->referral),
            'my_referral'       => $this->when($this->my_referral, $this->my_referral),
            'role'              => $this->when($role, $role),
            'email_verified_at' => $this->when($this->email_verified_at,
                $this->email_verified_at?->format('Y-m-d H:i:s') . 'Z'
            ),
            'phone_verified_at' => $this->when($this->phone_verified_at,
                $this->phone_verified_at?->format('Y-m-d H:i:s') . 'Z'
            ),
            'registered_at'     => $this->when($this->created_at,
                $this->created_at?->format('Y-m-d H:i:s') . 'Z'
            ),
            'reviews_avg_rating' => $this->when($this->reviews_avg_rating, $this->reviews_avg_rating),
            'reviews_count'      => $this->when($this->reviews_count, $this->reviews_count),
            'assign_reviews_avg_rating' => $this->when($this->assign_reviews_avg_rating,
                $this->assign_reviews_avg_rating
            ),
            'review_count_by_rating'        => $this->when($reviewsGroup,   $reviewsGroup),
            'r_count'                       => $this->when($this->r_count,  $this->r_count),
            'r_avg'                         => $this->when($this->r_avg,    $this->r_avg),
            'r_sum'                         => $this->when($this->r_sum,    $this->r_sum),
            'o_count'                       => $this->when($this->o_count,  $this->o_count),
            'o_sum'                         => $this->when($this->o_sum,    $this->o_sum),
            'lang'                          => $this->when($this->lang,     $this->lang),
            'feedbacks_count'               => $this->when($this->feed_backs_count, $this->feed_backs_count),
            'not_helpful'                   => $this->when($this->not_helpful, $this->not_helpful),
            'helpful'                       => $this->when($this->helpful,     $this->helpful),
            'created_at'                    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'                    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'referral_from_topup_price'     => $this->when(request('referral'), $this->referral_from_topup_price),
            'referral_from_withdraw_price'  => $this->when(request('referral'), $this->referral_from_withdraw_price),
            'referral_to_withdraw_price'    => $this->when(request('referral'), $this->referral_to_withdraw_price),
            'referral_to_topup_price'       => $this->when(request('referral'), $this->referral_to_topup_price),
            'referral_from_topup_count'     => $this->when(request('referral'), $this->referral_from_topup_count),
            'referral_from_withdraw_count'  => $this->when(request('referral'), $this->referral_from_withdraw_count),
            'referral_to_withdraw_count'    => $this->when(request('referral'), $this->referral_to_withdraw_count),
            'referral_to_topup_count'       => $this->when(request('referral'), $this->referral_to_topup_count),
            'orders'                        => OrderResource::collection($this->whenLoaded('orders')),
            'orders_count'                  => $this->when($this->orders_count, $this->orders_count),
            'email_subscribe'               => $this->whenLoaded('emailSubscription'),
            'notifications'                 => $this->whenLoaded('notifications'),
            'wallet'                        => WalletResource::make($this->whenLoaded('wallet')),
            'reviews'                       => ReviewResource::collection($this->whenLoaded('reviews')),
            'assign_reviews'                => ReviewResource::collection($this->whenLoaded('assignReviews')),
            'currency'                      => CurrencyResource::make($this->whenLoaded('currency')),
        ];
    }

    /**
     * @param $userId
     * @return array[]
     */
    public function prepareReviewCountGroup($userId): array
    {
        $reviews = DB::table('reviews')
            ->where('user_id', $userId)

            ->select([
                DB::raw('count(id) as count, rating')
            ])
            ->groupBy(['rating'])
            ->get();

        return Utility::groupRating($reviews);
    }
}
