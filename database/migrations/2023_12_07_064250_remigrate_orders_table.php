<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemigrateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->from(1000);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->double('total_price', 20);
            $table->float('rate')->default(1);
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('username')->nullable();
            $table->string('img')->nullable();
            $table->string('canceled_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->bigInteger('user_id')->nullable();
            $table->morphs('model');
            $table->bigInteger('parent_id')->default(0);
            $table->string('type')->default('question');
            $table->string('subject', 191);
            $table->text('content');
            $table->string('status')->default(Ticket::OPEN);
            $table->boolean('read')->default(false);
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
        //
    }
}
