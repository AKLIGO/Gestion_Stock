<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Seeder personnalisé pour les catégories demandées
        $categories = [
            ['name' => 'Alimentaire', 'description' => 'Produits alimentaires'],
            ['name' => 'Informatiques', 'description' => 'Produits informatiques'],
            ['name' => 'Vestimentaires', 'description' => 'Produits vestimentaires'],
        ];


        // Appel du ProductSeeder pour insérer les produits
        $this->call(ProductSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
