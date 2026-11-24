<?php

namespace Modules\Storefront\Services;

use Illuminate\Support\Facades\Http;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Entities\StoreSetting;

class PaystackGateway
{
    public function available(StoreSetting $store): bool
    {
        $key = (string) config('storefront.paystack_secret');
        return $store->enable_paystack && preg_match('/^sk_(test|live)_\S+$/', $key)
            && in_array($store->business->currency->code, ['GHS', 'NGN', 'ZAR', 'KES', 'USD']);
    }

    protected function client()
    {
        return Http::withToken((string) config('storefront.paystack_secret'))->acceptJson()->timeout(20)->connectTimeout(5);
    }

    public function initialize(StoreOrder $order): string
    {
        $response = $this->client()->post('https://api.paystack.co/transaction/initialize', [
            'email' => $order->customer['email'],
            'amount' => (string) $order->total_minor,
            'currency' => $order->currency,
            'reference' => $order->reference,
            'callback_url' => route('storefront.paystack.callback'),
            'metadata' => ['storefront_order' => $order->reference],
        ]);
        $url = $response->json('data.authorization_url');
        if (! $response->successful() || $response->json('status') !== true || ! is_string($url)
            || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'checkout.paystack.com') {
            throw new \RuntimeException('Paystack could not start payment. Your order is saved; please try again.');
        }
        return $url;
    }

    public function verify(StoreOrder $order): array
    {
        $response = $this->client()->get('https://api.paystack.co/transaction/verify/'.rawurlencode($order->reference));
        if (! $response->successful() || $response->json('status') !== true) {
            throw new \RuntimeException('Payment could not be verified yet. Please check again shortly.');
        }
        $data = $response->json('data');
        if (! is_array($data)) {
            throw new \RuntimeException('Payment verification returned an invalid response.');
        }
        if (($data['status'] ?? '') !== 'success') {
            return ['paid' => false, 'failed' => in_array($data['status'] ?? '', ['failed', 'abandoned', 'reversed'])];
        }
        $expectedDomain = str_starts_with((string) config('storefront.paystack_secret'), 'sk_test_') ? 'test' : 'live';
        if (($data['reference'] ?? '') !== $order->reference || (string) ($data['amount'] ?? '') !== (string) $order->total_minor
            || ($data['currency'] ?? '') !== $order->currency || empty($data['id'])
            || strcasecmp($data['customer']['email'] ?? '', $order->customer['email']) !== 0
            || (isset($data['domain']) && $data['domain'] !== $expectedDomain)) {
            throw new \RuntimeException('Payment details did not match this order. Please contact the store with your order reference.');
        }
        return ['paid' => true, 'id' => (string) $data['id']];
    }

    public function validSignature(string $body, ?string $modern, ?string $legacy): bool
    {
        $secret = (string) config('storefront.paystack_webhook_secret');
        // Once a signing secret is configured, do not downgrade to the legacy header.
        if ($secret !== '') {
            if (! $modern) { return false; }
            $timestamp = null;
            $signatures = [];
            foreach (explode(',', $modern) as $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
                if ($key === 't') {
                    if ($timestamp !== null || ! ctype_digit($value)) { return false; }
                    $timestamp = (int) $value;
                } elseif ($key === 'v1') {
                    $signatures[] = explode(':', $value, 2)[1] ?? '';
                }
            }
            if ($timestamp === null || abs(time() - $timestamp) > 300) { return false; }
            $expected = hash_hmac('sha512', $timestamp.'.'.$body, $secret);
            foreach ($signatures as $signature) {
                if (preg_match('/^[0-9a-f]{128}$/', $signature) && hash_equals($expected, $signature)) { return true; }
            }
            return false;
        }
        $key = (string) config('storefront.paystack_secret');
        return $key !== '' && is_string($legacy) && preg_match('/^[0-9a-f]{128}$/', $legacy)
            && hash_equals(hash_hmac('sha512', $body, $key), $legacy);
    }
}
