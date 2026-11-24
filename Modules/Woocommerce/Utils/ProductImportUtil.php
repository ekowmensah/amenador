<?php

namespace Modules\Woocommerce\Utils;

use App\Business;
use App\Category;
use App\Product;
use App\VariationGroupPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductImportUtil
{
    protected $woocommerce;

    public function __construct(WoocommerceUtil $woocommerce)
    {
        $this->woocommerce = $woocommerce;
    }

    public function sync(Business $business, int $remoteId, $remote = null): bool
    {
        $settings = $this->woocommerce->get_api_settings($business->id);

        return DB::transaction(function () use ($business, $remoteId, $settings, $remote) {
            $product = Product::where('business_id', $business->id)
                ->where('woocommerce_product_id', $remoteId)
                ->where('woocommerce_disable_sync', 0)
                ->lockForUpdate()->first();
            if (! $product) {
                return false;
            }

            // Fetch current data instead of applying a potentially delayed webhook payload.
            $remote = $remote ?? $this->woocommerce->woo_client($business->id)->get('products/'.$remoteId);
            $fields = $settings->product_fields_for_import ?? ['name', 'description', 'image', 'weight', 'category', 'price'];
            foreach (['name' => 'name', 'weight' => 'weight'] as $field => $attribute) {
                if (in_array($field, $fields) && isset($remote->$field)) {
                    $product->$attribute = $remote->$field;
                }
            }
            if (in_array('description', $fields)) {
                $field = ($settings->sync_description_as ?? 'long') === 'short' ? 'short_description' : 'description';
                if (isset($remote->$field)) {
                    $product->product_description = $remote->$field;
                }
            }
            if (in_array('category', $fields) && isset($remote->categories)) {
                $categories = Category::where('business_id', $business->id)
                    ->where('category_type', 'product')
                    ->whereIn('woocommerce_cat_id', array_column($remote->categories, 'id'))->get();
                $parent = $categories->firstWhere('parent_id', 0);
                $child = $categories->first(function ($category) use ($parent) {
                    return $parent && $category->parent_id == $parent->id;
                });
                // Do not silently replace unmapped WordPress categories.
                if (empty($remote->categories) || $parent) {
                    $product->category_id = $parent ? $parent->id : null;
                    $product->sub_category_id = $child ? $child->id : null;
                }
            }
            if (in_array('image', $fields) && isset($remote->images)) {
                $image = $remote->images[0] ?? null;
                if ($image && ! empty($image->id)) {
                    $product->image = $this->downloadImage($image->src, $settings->woocommerce_app_url, $business->id);
                    $product->woocommerce_media_id = $image->id;
                } elseif (! empty($product->woocommerce_media_id)) {
                    $product->image = null;
                    $product->woocommerce_media_id = null;
                }
            }

            $changed = $product->isDirty();
            if ($changed) {
                $product->save();
            }
            if ($product->type === 'single' && in_array('price', $fields)
                && isset($remote->regular_price) && is_numeric($remote->regular_price)) {
                $variation = $product->variations()->first();
                if ($variation) {
                    $price = (float) $remote->regular_price;
                    $tax = $product->product_tax ? (float) $product->product_tax->amount : 0;
                    $inclusive = ($settings->product_tax_type ?? 'inc') === 'inc';
                    $exc = $inclusive ? $price / (1 + $tax / 100) : $price;
                    $inc = $inclusive ? $price : $price * (1 + $tax / 100);
                    if (! empty($settings->default_selling_price_group)) {
                        $group = VariationGroupPrice::firstOrNew([
                            'variation_id' => $variation->id,
                            'price_group_id' => $settings->default_selling_price_group,
                        ]);
                        $group->price_inc_tax = $inc;
                        $group->price_type = 'fixed';
                        if ($group->isDirty()) {
                            $group->save();
                            $changed = true;
                        }
                    } else {
                        // Compare at database precision to avoid rounding-only webhook echoes.
                        if (abs((float) $variation->default_sell_price - $exc) > 0.0001
                            || abs((float) $variation->sell_price_inc_tax - $inc) > 0.0001) {
                            $variation->default_sell_price = $exc;
                            $variation->sell_price_inc_tax = $inc;
                            $variation->save();
                            $changed = true;
                        }
                    }
                }
            }
            if ($changed) {
                $this->woocommerce->createSyncLog($business->id, $business->owner_id, 'product_import', 'updated', [$product->sku]);
            }

            return $changed;
        });
    }

    public function receiveNewerProducts(Business $business, $products): int
    {
        $client = $this->woocommerce->woo_client($business->id);
        $updated = 0;
        foreach ($products as $product) {
            if (empty($product->woocommerce_product_id) || $product->woocommerce_disable_sync) {
                continue;
            }
            $remote = $client->get('products/'.$product->woocommerce_product_id);
            $modified = $remote->date_modified_gmt ?? null;
            if (! $modified) {
                continue;
            }
            $remoteTime = \Carbon\Carbon::parse($modified, 'UTC');
            if ($product->updated_at && $remoteTime->lessThanOrEqualTo($product->updated_at)) {
                continue;
            }
            if ($this->sync($business, (int) $product->woocommerce_product_id, $remote)) {
                $updated++;
            }
        }

        return $updated;
    }

    protected function downloadImage(string $url, string $storeUrl, int $businessId): string
    {
        // Only fetch media from the configured store. Redirects must not escape this host.
        $parts = parse_url($url);
        $store = parse_url($storeUrl);
        if (! $parts || ! $store || ! in_array($parts['scheme'] ?? '', ['http', 'https'])
            || empty($parts['host']) || strcasecmp($parts['host'], $store['host'] ?? '') !== 0
            || ($parts['port'] ?? null) !== ($store['port'] ?? null)
            || isset($parts['user']) || isset($parts['pass'])) {
            throw new \RuntimeException('Product image must be hosted on the configured WooCommerce store.');
        }
        $response = Http::timeout(30)->withOptions(['allow_redirects' => false, 'stream' => true])->get($url);
        if ($response->status() !== 200) {
            throw new \RuntimeException('Unable to download WooCommerce product image.');
        }
        $stream = $response->toPsrResponse()->getBody();
        $bytes = '';
        try {
            while (! $stream->eof()) {
                $bytes .= $stream->read(8192);
                if (strlen($bytes) > 10 * 1024 * 1024) {
                    throw new \RuntimeException('WooCommerce image exceeds 10 MB.');
                }
            }
        } finally {
            $stream->close();
        }
        $info = @getimagesizefromstring($bytes);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        if (! $info || ! isset($extensions[$info['mime']])) {
            throw new \RuntimeException('Unsupported WooCommerce product image format.');
        }
        $filename = 'woo_'.$businessId.'_'.hash('sha256', $bytes).'.'.$extensions[$info['mime']];
        $directory = public_path('uploads/'.config('constants.product_img_path'));
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create product image directory.');
        }
        if (! is_file($directory.'/'.$filename) && file_put_contents($directory.'/'.$filename, $bytes, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to save WooCommerce product image.');
        }

        return $filename;
    }
}
