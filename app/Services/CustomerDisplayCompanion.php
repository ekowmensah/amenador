<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerDisplayCompanion
{
    public function registerHash(string $register): string
    {
        return hash('sha256', $register);
    }

    public function devices(int $business, int $user, string $register)
    {
        return DB::table('customer_display_devices')->where('business_id', $business)
            ->where('user_id', $user)->where('register_hash', $this->registerHash($register));
    }

    public function code(int $business, int $user, string $register): string
    {
        $code = (string) random_int(10000000, 99999999);
        Cache::put($this->pairKey($code), [
            'business_id' => $business, 'user_id' => $user, 'register_hash' => $this->registerHash($register),
        ], now()->addMinutes(5));

        return $code;
    }

    private function pairKey(string $code): string
    {
        return 'customer-display:pair:'.hash('sha256', $code);
    }

    public function pair(string $code, string $name): ?array
    {
        // Cache::add is atomic for supported shared cache drivers; one code creates one device.
        $key = $this->pairKey($code);
        if (!Cache::add($key.':claimed', true, now()->addMinutes(5))) {
            return null;
        }
        $pair = Cache::pull($key);
        if (!$pair) {
            return null;
        }
        $token = Str::random(64);
        $id = DB::table('customer_display_devices')->insertGetId($pair + [
            'name' => $name, 'token_hash' => hash('sha256', $token),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['token' => $token, 'device_id' => $id];
    }

    public function authenticate(?string $token): ?object
    {
        if (!$token || strlen($token) !== 64) {
            return null;
        }

        return DB::table('customer_display_devices')->where('token_hash', hash('sha256', $token))->first();
    }

    private function stateKey(object $device): string
    {
        return 'customer-display:state:'.$device->business_id.':'.$device->user_id.':'.$device->register_hash;
    }

    public function publish(int $business, int $user, string $register, array $order, bool $active, string $owner = ''): void
    {
        $device = (object) ['business_id' => $business, 'user_id' => $user, 'register_hash' => $this->registerHash($register)];
        if ($owner !== '') {
            $lease = $this->stateKey($device).':owner';
            $claimed = Cache::add($lease, $owner, now()->addSeconds(7));
            abort_unless($claimed || Cache::get($lease) === $owner, 409, 'Register is already open in another tab.');
            Cache::put($lease, $owner, now()->addSeconds(7));
        }
        Cache::put($this->stateKey($device), [
            'order' => $order, 'active' => $active, 'updated_at' => now()->timestamp,
        ], now()->addSeconds(30));
    }

    public function state(object $device): array
    {
        $state = Cache::get($this->stateKey($device));
        DB::table('customer_display_devices')->where('id', $device->id)
            ->where(function ($query) { $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subSeconds(30)); })
            ->update(['last_seen_at' => now()]);
        if (!$state || $state['updated_at'] < now()->subSeconds(15)->timestamp) {
            return ['active' => false, 'order' => null, 'connected' => false];
        }

        return $state + ['connected' => true];
    }
}
