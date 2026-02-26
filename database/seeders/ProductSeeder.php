<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Récupération des IDs de catégories existantes
        $categories = Category::pluck('id', 'name');

        $products = [
            ['name' => 'Riz parfumé', 'code_qr' => '100000000001', 'price' => 25.50, 'stock' => 100, 'description' => 'Riz parfumé de qualité supérieure', 'category' => 'Alimentaire'],
            ['name' => 'Ordinateur portable', 'code_qr' => '100000000002', 'price' => 750.00, 'stock' => 20, 'description' => 'PC portable performant', 'category' => 'Informatiques'],
            ['name' => 'T-shirt coton', 'code_qr' => '100000000003', 'price' => 15.00, 'stock' => 200, 'description' => 'T-shirt 100% coton', 'category' => 'Vestimentaires'],
        ];
        foreach ($products as $prod) {
            if (isset($categories[$prod['category']])) {
                Product::updateOrCreate(
                    [
                        'code_qr' => $prod['code_qr'],
                    ],
                    [
                        'category_id' => $categories[$prod['category']],
                        'name' => $prod['name'],
                        'price' => $prod['price'],
                        'stock' => $prod['stock'],
                        'description' => $prod['description'],
                    ]
                );
            }
        }
    }
}
