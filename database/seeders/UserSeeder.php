<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Services\UserServices\UserWalletService;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

class UserSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 102,
                'uuid' => Str::uuid(),
                'firstname' => 'User',
                'lastname' => 'User',
                'email' => 'user@githubit.com',
                'phone' => '998911902595',
                'birthday' => '1993-12-30',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('user123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'uuid' => Str::uuid(),
                'firstname' => 'Owner',
                'lastname' => 'Owner',
                'email' => 'owner@githubit.com',
                'phone' => '998911902696',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('githubit'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 104,
                'uuid' => Str::uuid(),
                'firstname' => 'Manager',
                'lastname' => 'Manager',
                'email' => 'manager@githubit.com',
                'phone' => '998911902616',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('manager'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {

            try {
                $user = User::updateOrCreate(['id' => data_get($user, 'id')], $user);

                (new UserWalletService)->create($user);

                $id = Notification::where('type', Notification::PUSH)
                    ->select(['id', 'type'])
                    ->first()
                    ?->id;

                $user->notifications()->sync([$id]);
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

        User::find(102)?->syncRoles('user');
        User::find(103)?->syncRoles(['admin']);
        User::find(104)?->syncRoles('manager');
    }

}
