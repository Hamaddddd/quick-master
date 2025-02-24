<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('ads_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('active')->default(false)->comment('Активный');
            $table->smallInteger('count')->default(0)->comment('Кол-во объявлений');
            $table->string('time_type')->default('day')->comment('Тип времени рекламы: минут,час,день,недель,месяц,год');
            $table->smallInteger('time')->comment('Время');
            $table->double('price')->default(0);
            $table->timestamps();
        });

        Schema::create('ads_package_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_package_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description');
            $table->text('button_text');
            $table->unique(['ads_package_id', 'locale']);
        });

        Schema::create('user_ads_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_package_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->smallInteger('count')->default(0)->comment('Кол-во объявлений');
            $table->string('time_type')->default('day')->comment('Тип времени рекламы: минут,час,день,недель,месяц,год');
            $table->smallInteger('time')->comment('Время');
            $table->double('price')->default(0);
            $table->timestamps();
        });

        Schema::create('product_ads_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_ads_package_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('expired_at')->nullable();
            $table->smallInteger('input')->default(1)->comment('Выводить в топ по очереди');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ads_packages');
        Schema::dropIfExists('user_ads_packages');
        Schema::dropIfExists('ads_package_translations');
        Schema::dropIfExists('ads_packages');
    }
}
