<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['category', 'images'])
            ->withCount('images')
            ->paginate();

        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $category = Category::where('name', $validated['category_name'])->firstOrFail();
        $validated['category_id'] = $category->id;
        unset($validated['category_name']);

        $product = DB::transaction(function () use ($validated) {
            $images = $validated['images'] ?? [];
            unset($validated['images']);

            $product = Product::create($validated);

            if ($images) {
                $product->images()->createMany($this->storeImageFiles($images));
            }

            return $product->load(['category', 'images']);
        });

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images']);

        return response()->json($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('category_name', $validated)) {
            $category = Category::where('name', $validated['category_name'])->firstOrFail();
            $validated['category_id'] = $category->id;
            unset($validated['category_name']);
        }

        $product = DB::transaction(function () use ($validated, $product) {
            $images = $validated['images'] ?? null;
            unset($validated['images']);

            if (! empty($validated)) {
                $product->update($validated);
            }

            if ($images !== null) {
                $this->deleteProductStoredImages($product);
                $product->images()->delete();

                if ($images) {
                    $product->images()->createMany($this->storeImageFiles($images));
                }
            }

            return $product->load(['category', 'images']);
        });

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    // Stores uploaded files and returns payloads prepared for DB insertion.
    private function storeImageFiles(array $images): array
    {
        return array_map(static function (array $image) {
            $storedPath = $image['file']->store('product-images', 'public');

            return [
                'path' => Storage::url($storedPath),
                'is_primary' => $image['is_primary'] ?? false,
            ];
        }, $images);
    }

    private function deleteProductStoredImages(Product $product): void
    {
        $product->images->each(function ($image) {
            if (! empty($image->path)) {
                Storage::disk('public')->delete($this->extractStoragePath($image->path));
            }
        });
    }

    private function extractStoragePath(string $url): string
    {
        if (str_starts_with($url, '/storage/')) {
            return substr($url, 9);
        }

        return ltrim($url, '/');
    }


    public function indexWeb(){
        $query = Product::with(['category', 'images'])->withCount('images');

        // Filtre par catégorie
        if ($categoryId = request('category')) {
            $query->where('category_id', $categoryId);
        }

        // Filtre par recherche
        if ($search = request('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $products = $query->paginate();
        $categories = Category::all();
        return view('home', compact('products', 'categories'));
    }

    public function showWeb(Product $product){
        $product->load(['category', 'images']);
        return view('product.show', compact('product'));
    }

    public function payWeb(Product $product)
    {
        $quantity = request('quantity');
        // Validation simple
        if (!$quantity || $quantity < 1 || $quantity > $product->stock) {
            return redirect()->back()->with('error', 'Quantité invalide');
        }
        // Ici, on peut préparer la logique d'intégration CinetPay
        // Exemple: sauvegarder la demande, rediriger vers la page de paiement, etc.
        // Pour l'instant, on affiche juste un message de confirmation
        return redirect()->back()->with('success', 'Paiement pour ' . $quantity . ' unité(s) du produit lancé.');
    }
}
