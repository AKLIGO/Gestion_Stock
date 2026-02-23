<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function index(): JsonResponse
    {
        $images = ProductImage::with('product')->paginate();

        return response()->json($images);
    }

    public function store(StoreProductImageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $storedPath = $request->file('image')->store('product-images', 'public');

        $image = ProductImage::create([
            'product_id' => $validated['product_id'],
            'path' => Storage::url($storedPath),
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        return response()->json($image->load('product'), 201);
    }

    public function show(ProductImage $productImage): JsonResponse
    {
        $productImage->load('product');

        return response()->json($productImage);
    }

    public function update(UpdateProductImageRequest $request, ProductImage $productImage): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('product-images', 'public');

            if ($productImage->path) {
                Storage::disk('public')->delete($this->extractStoragePath($productImage->path));
            }

            $validated['path'] = Storage::url($newPath);
        }

        $productImage->update($validated);

        return response()->json($productImage->load('product'));
    }

    public function destroy(ProductImage $productImage): JsonResponse
    {
        if ($productImage->path) {
            Storage::disk('public')->delete($this->extractStoragePath($productImage->path));
        }

        $productImage->delete();

        return response()->json(null, 204);
    }

    private function extractStoragePath(string $url): string
    {
        if (str_starts_with($url, '/storage/')) {
            return substr($url, 9);
        }

        return ltrim($url, '/');
    }
}
