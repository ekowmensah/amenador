<?php

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Services\PublicStoreLink;

class HostIntegration
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (! $response->isSuccessful()) { return $response; }
        $type = $response->headers->get('Content-Type', '');
        if ($request->is('/') && ($type === '' || str_contains($type, 'text/html'))) {
            $this->homepage($response);
        }
        $action = $request->route() ? ltrim($request->route()->getActionName(), '\\') : '';
        if ($response instanceof JsonResponse && $action === 'App\\Http\\Controllers\\SellController@index') {
            $this->sales($request, $response);
        }
        return $response;
    }

    protected function homepage($response): void
    {
        $html = $response->getContent();
        if (! str_contains($html, '</body>') || str_contains($html, 'data-storefront-promo') || str_contains($html, 'public-shop-hero')) { return; }
        $data = app(PublicStoreLink::class)->data();
        if (! $data['publicStoreUrl']) { return; }
        $hero = (string) view('storefront::public_promo', $data + ['placement' => 'hero']);
        $count = 0;
        $html = preg_replace_callback('/(<div\b[^>]*class="[^"]*\bbar-actions\b[^"]*"[^>]*>)/i', fn ($match) => $match[0].$hero, $html, 1, $count);
        if ($count) {
            $nav = (string) view('storefront::public_promo', $data + ['placement' => 'nav']);
            $html = preg_replace_callback('/(<nav\b[^>]*class="[^"]*\bbar-nav-links\b[^"]*"[^>]*>)/i', fn ($match) => $match[0].$nav, $html, 1);
        } else {
            $promo = (string) view('storefront::public_promo', $data + ['placement' => 'floating']);
            $html = str_replace('</body>', $promo.'</body>', $html);
        }
        $css = '<link rel="stylesheet" href="'.e(route('storefront.asset', 'homepage.css')).'?v='.e(config('storefront.module_version')).'">';
        $html = str_contains($html, '</head>') ? str_replace('</head>', $css.'</head>', $html) : $css.$html;
        $response->setContent($html);
        $response->headers->remove('Content-Length');
        $response->headers->remove('ETag');
    }

    protected function sales($request, JsonResponse $response): void
    {
        $businessId = (int) ($request->session()->get('business.id') ?: $request->session()->get('user.business_id'));
        $payload = $response->getData(true);
        if (! $businessId || ! isset($payload['data']) || ! is_array($payload['data']) || ! Schema::hasTable('storefront_orders')) { return; }
        $ids = array_column($payload['data'], 'id');
        if (! $ids) { return; }
        $orders = StoreOrder::where('business_id', $businessId)->whereIn('transaction_id', $ids)->get()->keyBy('transaction_id');
        foreach ($payload['data'] as &$row) {
            $order = $orders->get($row['id'] ?? null);
            if ($order && isset($row['invoice_no']) && ! str_contains($row['invoice_no'], 'data-storefront-order')) {
                $row['invoice_no'] .= (string) view('storefront::sale_badge', compact('order'));
            }
        }
        unset($row);
        $response->setData($payload);
    }
}
