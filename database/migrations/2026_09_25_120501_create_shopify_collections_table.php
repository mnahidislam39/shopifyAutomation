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
        Schema::create('shopify_collections', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain');
            $table->string('shopify_id')->nullable();
            $table->string('title');
            $table->string('handle')->nullable();
            $table->string('type');                        // smart or custom
            $table->text('description')->nullable();       // ডেসক্রিপশন এর জন্য
            $table->string('template_suffix')->nullable(); // টেমপ্লেট সাফিক্স এর জন্য
            $table->text('image_url')->nullable();         // ইমেজ ইউআরএল এর জন্য
            $table->string('status');                      // success or failed
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_collections');
    }
};
