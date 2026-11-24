<?php

namespace Modules\Storefront\Entities;

use App\Business;
use App\BusinessLocation;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $table = 'storefront_settings';
    protected $guarded = ['id'];
    protected $casts = ['enabled' => 'boolean', 'allow_cash' => 'boolean', 'enable_paystack' => 'boolean', 'delivery_enabled' => 'boolean'];

    public function business() { return $this->belongsTo(Business::class); }
    public function location() { return $this->belongsTo(BusinessLocation::class); }
    public function getRouteKeyName() { return 'slug'; }

    public function deliveryAvailable(): bool
    {
        return $this->delivery_enabled && $this->delivery_fee !== null;
    }
}
