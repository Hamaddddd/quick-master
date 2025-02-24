<?php
declare(strict_types=1);

namespace App\Traits;

use App\Helpers\ResponseError;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\Language;
use App\Models\Order;
use App\Models\PushNotification;
use App\Models\Settings;
use App\Models\User;
use App\Services\PushNotificationService\PushNotificationService;
use Illuminate\Support\Facades\Http;

trait Notification
{
    private string $url = 'https://fcm.googleapis.com/fcm/send';

    public function sendNotification(
        mixed $model,
        array|null $receivers = [],
        string|int|null $message = '',
        string|int|null $title = null,
        mixed $data = [],
        array $userIds = [],
    ): void
    {

        if (is_array($userIds) && count($userIds) > 0) {
            (new PushNotificationService)->storeMany([
                'type'  => (string)($data['type'] ?? @$data['order']['type']) ?? PushNotification::NEW_ORDER,
                'title' => $title,
                'body'  => $message,
                'data'  => $data,
            ], $userIds, $model);
        }

        if (empty($receivers)) {
            return;
        }

        $serverKey = $this->firebaseKey();

        $fields = [
            'registration_ids' => $receivers,
            'notification' => [
                'body'  => $message,
                'title' => $title,
                'sound' => 'default',
            ],
            'data' => $data
        ];

        $headers = [
            'Authorization' => "key=$serverKey",
            'Content-Type' => 'application/json'
        ];

// $result =
        Http::withHeaders($headers)->post($this->url, $fields);
//
//        Log::error('fcm', [
//            'count'     => count($receivers),
//            'users'     => count($userIds),
//            'model'     => $model,
//            'message'   => $message,
//            'title'     => $title,
//            'data'      => $data,
//            'userIds'   => $userIds,
//            'res'       => $result->json(),
//        ]);

    }

    public function sendAllNotification(
        Blog $model,
        mixed $data = [],
    ): void
    {
        User::select([
            'id',
            'active',
            'email_verified_at',
            'phone_verified_at',
            'firebase_token',
            'lang',
        ])
            ->where('active', 1)
            ->where(function ($query) {
                $query
                    ->whereNotNull('email_verified_at')
                    ->orWhereNotNull('phone_verified_at');
            })
            ->whereNotNull('firebase_token')
            ->orderBy('id')
            ->chunk(100, function ($users) use ($model, $data) {

                foreach ($users as $user) {
                    /** @var User $user */

                    if (!isset($this->language)) {
                        $locale = Language::where('default', 1)->first()?->locale;

                        $this->language = $locale;
                    }

                    $translation = $model->translations
                        ?->where('locale', $user->lang ?? $this->language)
                        ?->first();

                    if (empty($translation)) {
                        $translation = $model->translations?->first();
                    }

                    /** @var BlogTranslation $translation */
                    $this->sendNotification(
                        $model,
                        $user->firebase_token,
                        $translation?->short_desc,
                        $translation?->title,
                        $data,
                        [$user->id]
                    );
                }

            });
    }

    private function firebaseKey()
    {
        return Settings::where('key', 'server_key')->pluck('value')->first();
    }

    /**
     * @param array $result
     * @param string $class
     * @return void
     */
    public function adminNotify(array $result, string $class = Order::class): void
    {
        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin') )
            ->whereNotNull('firebase_token')
            ->select(['id', 'lang', 'firebase_token'])
            ->get();

        if ($class === Order::class) {

            foreach (data_get($result, 'data', []) as $order) {
                $this->sendUsers($order, $admins, $class);
            }

            return;
        }

        $this->sendUsers(data_get($result, 'data'), $admins, $class);
    }

    /**
     * @param Order $order
     * @param $users
     * @param string $class
     * @return void
     */
    private function sendUsers(Order $order, $users, string $class = Order::class): void
    {
        $type       = PushNotification::NEW_ORDER;
        $messageKey = ResponseError::NEW_ORDER;

        if (!isset($this->language)) {
            $locale = Language::where('default', 1)->first()?->locale;

            $this->language = $locale;
        }

        foreach ($users as $user) {

            if (empty($user)) {
                continue;
            }

            /** @var User $user */
            $this->sendNotification(
                $order,
                $user->firebase_token ?? [],
                __("errors.$messageKey", ['id' => $order->id], $user->lang ?? $this->language),
                __("errors.$messageKey", ['id' => $order->id], $user->lang ?? $this->language),
                [
                    'id'     => $order->id,
                    'type'   => $type
                ],
                [$user->id]
            );

        }

    }

}
