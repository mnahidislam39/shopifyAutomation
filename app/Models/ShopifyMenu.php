<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyMenu extends Model
{
    // সব ফিল্ডে একসাথে ডেটা সেভ (Mass Assignment) করার অনুমতি দিতে এটি দিন:
    protected $guarded = [];

    protected $casts = [
        'menu_items' => 'array',
    ];
}
