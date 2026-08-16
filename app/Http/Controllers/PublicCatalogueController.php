<?php

namespace App\Http\Controllers;

use App\Product;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicCatalogueController extends Controller
{
    /**
     * Public, read-only catalogue. Never expose stock quantities, costs,
     * suppliers, margins, lot numbers, expiry dates or internal identifiers.
     */
    public function products(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:80',
            'category' => 'nullable|integer|min:1',
            'location' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1|max:500',
            'per_page' => 'nullable|integer|min:1|max:24',
        ]);

        $businessId = (int) config('constants.public_catalog_business_id', 1);
        $perPage = (int) ($validated['per_page'] ?? 12);
        $locationId = null;
        if (! empty($validated['location'])) {
            $locationId = BusinessLocation::where('business_id', $businessId)
                ->where('is_active', 1)
                ->where('id', (int) $validated['location'])
                ->value('id');
        }

        $query = Product::query()
            ->leftJoin('categories as category', 'products.category_id', '=', 'category.id')
            ->leftJoin('variations as variation', function ($join) {
                $join->on('variation.product_id', '=', 'products.id')
                    ->whereNull('variation.deleted_at');
            })
            ->where('products.business_id', $businessId)
            ->where('products.is_inactive', 0)
            ->where('products.not_for_selling', 0);

        if (! empty($validated['q'])) {
            $search = trim($validated['q']);
            $query->where(function ($where) use ($search) {
                $where->where('products.name', 'like', '%'.$search.'%')
                    ->orWhere('products.sku', 'like', '%'.$search.'%')
                    ->orWhere('variation.sub_sku', 'like', '%'.$search.'%');
            });
        }

        if (! empty($validated['category'])) {
            $query->where('products.category_id', (int) $validated['category']);
        }

        if ($locationId) {
            $query->whereExists(function ($available) use ($locationId) {
                $available->select(DB::raw(1))
                    ->from('product_locations')
                    ->whereColumn('product_locations.product_id', 'products.id')
                    ->where('product_locations.location_id', $locationId);
            });
        }

        $products = $query
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.image', 'category.name')
            ->orderBy('products.name')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.image',
                'category.name as category',
                DB::raw('MIN(variation.sell_price_inc_tax) as price_from'),
                DB::raw('MAX(variation.sell_price_inc_tax) as price_to'),
            ])
            ->paginate($perPage);

        $items = collect($products->items())->map(function ($product) {
            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category ?: 'General',
                'image' => ! empty($product->image)
                    ? asset('uploads/img/'.rawurlencode($product->image))
                    : null,
                'price_from' => is_null($product->price_from) ? null : round((float) $product->price_from, 2),
                'price_to' => is_null($product->price_to) ? null : round((float) $product->price_to, 2),
            ];
        });

        $categories = DB::table('categories as category')
            ->join('products', 'products.category_id', '=', 'category.id')
            ->where('category.business_id', $businessId)
            ->where('category.category_type', 'product')
            ->where('products.is_inactive', 0)
            ->where('products.not_for_selling', 0)
            ->when($locationId, function ($categoryQuery) use ($locationId) {
                $categoryQuery->whereExists(function ($available) use ($locationId) {
                    $available->select(DB::raw(1))
                        ->from('product_locations')
                        ->whereColumn('product_locations.product_id', 'products.id')
                        ->where('product_locations.location_id', $locationId);
                });
            })
            ->groupBy('category.id', 'category.name')
            ->orderBy('category.name')
            ->select('category.id', 'category.name')
            ->get();

        $locations = BusinessLocation::where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'city'])
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'city' => $location->city,
                ];
            });

        return response()->json([
            'products' => $items,
            'categories' => $categories,
            'locations' => $locations,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ])->header('Cache-Control', 'public, max-age=60');
    }
}
