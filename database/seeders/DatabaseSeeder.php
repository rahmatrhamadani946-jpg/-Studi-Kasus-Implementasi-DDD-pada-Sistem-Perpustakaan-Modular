<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Models\Role;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Membuat admin@example.com (role: admin) dan user@example.com (role: user)
        $this->call([
            AdminUserSeeder::class,
        ]);

        // Seed roles (Identity & Access Context)
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $userRole = Role::create(['name' => 'User', 'slug' => 'user']);

        // Hubungkan user hasil AdminUserSeeder dengan Role (Many-to-Many)
        User::where('email', 'admin@example.com')->first()?->roles()->attach($adminRole);
        User::where('email', 'user@example.com')->first()?->roles()->attach($userRole);

        // Seed categories (Catalog Context)
        $categories = Category::factory()->count(10)->create();

        // Seed products
        $products = Product::factory()->count(50)->create();

        // Attach categories to products
        $products->each(function ($product) use ($categories) {
            $product->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray(),
                ['added_at' => now()]
            );
        });

        // Tugas Pertemuan 6: sistem perpustakaan (Catalog, Membership, Circulation)
        $this->call(LibrarySeeder::class);
    }
}
