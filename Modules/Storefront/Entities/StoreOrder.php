<?php

namespace Modules\Storefront\Entities;

use Illuminate\Database\Eloquent\Model;

class StoreOrder extends Model
{
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->tracking_code = $order->tracking_code ?: static::newTrackingCode();
        });
    }

    public static function newTrackingCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) { $code .= $alphabet[random_int(0, strlen($alphabet) - 1)]; }
            $code = 'SF-'.substr($code, 0, 4).'-'.substr($code, 4);
        } while (static::where('tracking_code', $code)->exists());
        return $code;
    }

    protected $table = 'storefront_orders';
    protected $guarded = ['id'];
    protected $casts = ['customer' => 'array', 'items' => 'array', 'verified_at' => 'datetime'];
    protected $hidden = ['customer', 'notes', 'checkout_key', 'authorization_url'];
}
