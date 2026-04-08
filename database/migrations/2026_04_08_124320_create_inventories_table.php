<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('on_hand')->default(0)->index();
            $table->integer('reserved')->default(0);
            $table->integer('reorder_point')->default(10)->index();
            $table->integer('reorder_quantity')->default(30);
            $table->integer('safety_stock')->default(5);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};