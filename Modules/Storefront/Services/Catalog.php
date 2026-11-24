<?php

namespace Modules\Storefront\Services;

use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Validation\ValidationException;
use Modules\Storefront\Entities\StoreSetting;

class Catalog
{
    public function query(StoreSetting $store)
    {
        return Variation::with(['product.category', 'product.sub_category'])
            ->whereHas('product', function ($query) use ($store) {
                $query->where('business_id', $store->business_id)->where('is_inactive', 0)
                    ->where('not_for_selling', 0)->whereIn('type', ['single', 'variable'])
                    ->whereHas('product_locations', function ($location) use ($store) {
                        $location->where('product_locations.location_id', $store->location_id);
                    });
            });
    }

    public function products(StoreSetting $store)
    {
        $variations = $this->query($store)->get();
        $stock = VariationLocationDetails::where('location_id', $store->location_id)
            ->whereIn('variation_id', $variations->pluck('id'))->pluck('qty_available', 'variation_id');

        return $variations->map(function ($variation) use ($stock) {
            $product = $variation->product;
            $name = $product->name;
            if ($product->type === 'variable' && $variation->name !== 'DUMMY') {
                $name .= ' — '.$variation->name;
            }
            $available = ! $product->enable_stock || ($stock[$variation->id] ?? 0) >= 1;
            return [
                'id' => $variation->id, 'name' => $name,
                'category_id' => $product->category_id,
                'subcategory_id' => $product->sub_category_id,
                'category' => $product->category->name ?? 'The collection',
                'image' => $product->image_url,
                'price_minor' => self::minor($variation->sell_price_inc_tax),
                'available' => $available,
            ];
        })->sortBy('name')->values();
    }

    public static function minor($value): int
    {
        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }

    public function quote(StoreSetting $store, array $cart, bool $lock = false): array
    {
        if (! $cart || count($cart) > 50) {
            throw ValidationException::withMessages(['cart' => 'Add up to 50 different items to your order.']);
        }
        ksort($cart, SORT_NUMERIC);
        $query = $this->query($store)->whereIn('id', array_keys($cart))->orderBy('id');
        $variations = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('id');
        $items = [];
        foreach ($cart as $id => $quantity) {
            $variation = $variations->get($id);
            if (! $variation || ! is_numeric($quantity) || $quantity < 1 || $quantity > 99 || (int) $quantity != $quantity) {
                throw ValidationException::withMessages(['cart' => 'An item is unavailable. Please review your cart.']);
            }
            $product = $variation->product;
            $stockQuery = VariationLocationDetails::where('variation_id', $id)->where('location_id', $store->location_id);
            $stock = ($lock ? $stockQuery->lockForUpdate() : $stockQuery)->first();
            if ($product->enable_stock && (! $stock || $stock->qty_available < $quantity)) {
                throw ValidationException::withMessages(['cart' => $product->name.': the requested quantity is not available.']);
            }
            $price = self::minor($variation->sell_price_inc_tax);
            if ($price < 0) {
                throw ValidationException::withMessages(['cart' => 'This item does not have a valid price.']);
            }
            $items[] = [
                'variation_id' => (int) $id, 'product_id' => $product->id,
                'name' => $product->name.($product->type === 'variable' ? ' — '.$variation->name : ''),
                'quantity' => (int) $quantity, 'price_minor' => $price,
                'base_minor' => self::minor($variation->default_sell_price),
                'tax_id' => $product->tax, 'enable_stock' => (bool) $product->enable_stock,
                'image' => $product->image_url,
            ];
        }
        return ['items' => $items, 'subtotal_minor' => array_sum(array_map(function ($item) { return $item['price_minor'] * $item['quantity']; }, $items))];
    }

    public function assertSnapshotAvailable(StoreSetting $store, array $items): void
    {
        $this->quote($store, array_column($items, 'quantity', 'variation_id'), true);
    }

    public function fingerprint(StoreSetting $store, array $quote): string
    {
        $prices = array_map(function ($item) {
            return [$item['variation_id'], $item['quantity'], $item['price_minor'], $item['base_minor'], $item['tax_id']];
        }, $quote['items']);
        return hash('sha256', json_encode([$store->location_id, $store->business->currency->code, $prices,
            $store->deliveryAvailable(), $store->delivery_fee === null ? null : self::minor($store->delivery_fee)]));
    }

    public function money(StoreSetting $store, int $minor): string
    {
        $currency = $store->business->currency;
        $settings = $store->business;
        $number = number_format($minor / 100, 2, $currency->decimal_separator ?: '.', $currency->thousand_separator ?: ',');
        return $settings->currency_symbol_placement === 'after' ? $number.' '.$currency->symbol : $currency->symbol.' '.$number;
    }
}
