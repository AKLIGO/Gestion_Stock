<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product_with_category_name_and_images(): void
    {
        $category = Category::factory()->create([
            'name' => 'Informatique',
        ]);

        Storage::fake('public');

        $response = $this->post('/api/products', [
            'category_name' => $category->name,
            'name' => 'Ordinateur Portable Pro',
            'code_qr' => 'QR-ORDI-001',
            'price' => 1299.99,
            'stock' => 5,
            'description' => 'Ultrabook performant pour usage professionnel.',
            'images' => [
                ['file' => UploadedFile::fake()->image('ordi-front.jpg'), 'is_primary' => true],
                ['file' => UploadedFile::fake()->image('ordi-side.jpg')],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => 'Ordinateur Portable Pro',
                'code_qr' => 'QR-ORDI-001',
            ])
            ->assertJsonPath('category.name', 'Informatique')
            ->assertJsonCount(2, 'images');

        $this->assertDatabaseHas('products', [
            'name' => 'Ordinateur Portable Pro',
            'category_id' => $category->id,
        ]);

        $product = Product::where('code_qr', 'QR-ORDI-001')->firstOrFail();

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'is_primary' => false,
        ]);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($this->extractStoragePath($image->path));
        }
    }

    private function extractStoragePath(string $url): string
    {
        if (str_starts_with($url, '/storage/')) {
            return substr($url, 9);
        }

        return ltrim($url, '/');
    }
}
