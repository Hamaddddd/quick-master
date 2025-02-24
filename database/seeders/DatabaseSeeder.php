<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(LanguageSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(TranslationSeeder::class);
        $this->call(EmailSettingSeeder::class);
        $this->call(SmsGatewaySeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(UserSeeder::class);
    }
}
