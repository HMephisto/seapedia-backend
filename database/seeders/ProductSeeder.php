<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sellerRole = Role::where('name', 'SELLER')->first();

        $seller = User::create([
            'full_name'      => 'John Seller',
            'email'          => 'seller@example.com',
            'password'       => Hash::make('password123'),
            'active_role_id' => $sellerRole->id,
        ]);

        $seller->roles()->attach($sellerRole->id);

        // Create store
        $store = Store::create([
            'seller_id'      => $seller->id,
            'store_name'     => 'Sea Fresh Store',
            'description'    => 'The freshest seafood in town',
            'address_detail' => 'Jl. Laut No. 1, Bandung',
        ]);

        // Create 3 products
        $products = [
            [
                'name'        => 'Fresh Tuna',
                'description' => 'Fresh caught tuna straight from the ocean, great for sashimi or grilling',
                'price'       => 85000,
                'stock'       => 50,
                'image_url'   => 'https://placehold.co/400x400?text=Fresh+Tuna',
            ],
            [
                'name'        => 'Tiger Prawns',
                'description' => 'Large tiger prawns, perfect for grilling or stir fry',
                'price'       => 120000,
                'stock'       => 30,
                'image_url'   => 'https://placehold.co/400x400?text=Tiger+Prawns',
            ],
            [
                'name'        => 'Blue Crab',
                'description' => 'Live blue crab, sold per kg, best for steaming or soup',
                'price'       => 95000,
                'stock'       => 20,
                'image_url'   => 'https://placehold.co/400x400?text=Blue+Crab',
            ],
        ];

        foreach ($products as $product) {
            Product::create(array_merge($product, ['store_id' => $store->id]));
        }
    }
}
