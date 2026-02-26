<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            [
                'product_id' => 1,
                'path' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80',
                'is_primary' => true,
            ],
            [
                'product_id' => 2,
                'path' => 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=400&q=80',
                'is_primary' => true,
            ],
            [
                'product_id' => 3,
                'path' => 'https://images.unsplash.com/photo-1526178613658-3e1c0b4b1c4c?auto=format&fit=crop&w=400&q=80',
                'is_primary' => true,
            ],
            [
                'product_id' => 3,
                'path' => 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=400&q=80',
                'is_primary' => false,
            ],
        ];

        foreach ($images as $img) {
            ProductImage::create($img);
        }
    }
}
