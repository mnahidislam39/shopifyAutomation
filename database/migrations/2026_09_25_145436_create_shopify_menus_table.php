<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_menus', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain');                    // Store domain (e.g., example.myshopify.com)
            $table->string('menu_title');                     // Menu Title (e.g., Main Navigation)
            $table->string('menu_handle')->nullable();        // Menu Handle (e.g., main-navigation)
            $table->json('menu_items');                       // Nested JSON structure for items and sub-menus
            $table->string('shopify_menu_id')->nullable();    // Shopify-theke fire asha menu GID (e.g., gid://shopify/Menu/...)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_menus');
    }
};
