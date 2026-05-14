<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Jobs\SyncZenditGiftCardsJob;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminCatalogController extends Controller
{
    public function syncZendit()
    {
        // Dispatch the job to sync the first page
        SyncZenditGiftCardsJob::dispatch(1);

        return response()->json([
            'message' => 'Zendit catalog sync job dispatched successfully.',
        ]);
    }

    public function products(Request $request)
    {
        $products = Product::with(['category', 'subcategory', 'variants'])
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return ProductResource::collection($products);
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return response()->json(['is_active' => $product->is_active]);
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return response()->json(['is_featured' => $product->is_featured]);
    }

    public function togglePopular(Product $product)
    {
        $product->update(['is_popular' => ! $product->is_popular]);

        return response()->json(['is_popular' => $product->is_popular]);
    }
}
