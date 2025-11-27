<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Product;

class MainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        $categoriesData = [
            ['name' => 'Sembako', 'image_url' => 'images/categories/sembako.png'],
            ['name' => 'Kerajinan', 'image_url' => 'images/categories/kerajinan.png'],
            ['name' => 'Pakaian', 'image_url' => 'images/categories/pakaian.png'],
            ['name' => 'Elektronik', 'image_url' => 'images/categories/elektronik.png'],
        ];

        $productsData = [
            [
                'category' => 'Sembako',
                'name' => 'Beras Premium',
                'description' => 'Beras kualitas premium untuk kebutuhan rumah tangga.',
                'price' => 120000,
                'stock' => 50,
                'image_path' => 'products/beras.png',
            ],
            [
                'category' => 'Sembako',
                'name' => 'Minyak Goreng Sunflower',
                'description' => 'Minyak goreng sehat dengan kandungan lemak tak jenuh.',
                'price' => 45000,
                'stock' => 80,
                'image_path' => 'products/minyak.png',
            ],
            [
                'category' => 'Kerajinan',
                'name' => 'Tas Kulit Handmade',
                'description' => 'Tas kulit asli buatan perajin lokal.',
                'price' => 350000,
                'stock' => 20,
                'image_path' => 'products/tas.png',
            ],
            [
                'category' => 'Kerajinan',
                'name' => 'Batik Tulis Eksklusif',
                'description' => 'Kain batik tulis dengan motif klasik.',
                'price' => 500000,
                'stock' => 15,
                'image_path' => 'products/batik.png',
            ],
            [
                'category' => 'Pakaian',
                'name' => 'Kemeja Linen Pria',
                'description' => 'Kemeja linen nyaman untuk kegiatan sehari-hari.',
                'price' => 250000,
                'stock' => 35,
                'image_path' => 'products/kemeja.png',
            ],
            [
                'category' => 'Elektronik',
                'name' => 'Earphone Nirkabel',
                'description' => 'Earphone nirkabel dengan kualitas suara jernih.',
                'price' => 750000,
                'stock' => 40,
                'image_path' => 'products/earphone.png',
            ],
        ];

        $categories = [];

        Model::unguarded(function () use ($categoriesData, &$categories) {
            foreach ($categoriesData as $categoryData) {
                $category = Category::create($categoryData);
                $categories[$category->name] = $category->id;
            }
        });

        Model::unguarded(function () use ($productsData, $categories) {
            foreach ($productsData as $productData) {
                Product::create([
                    'category_id' => $categories[$productData['category']] ?? null,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'image_path' => $productData['image_path'],
                ]);
            }
        });
    }
}



