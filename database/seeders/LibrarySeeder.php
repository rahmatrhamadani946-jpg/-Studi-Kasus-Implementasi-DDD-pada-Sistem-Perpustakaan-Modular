<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookCategory;
use Modules\Catalog\Models\Publisher;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\MemberRole;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // ---- Membership Context ----
        $student = MemberRole::create(['name' => 'Mahasiswa', 'slug' => 'student', 'max_loans' => 3, 'loan_period_days' => 14]);
        $lecturer = MemberRole::create(['name' => 'Dosen', 'slug' => 'lecturer', 'max_loans' => 5, 'loan_period_days' => 30]);

        $members = collect()
            ->merge(Member::factory()->count(6)->create(['member_role_id' => $student->id]))
            ->merge(Member::factory()->count(2)->create(['member_role_id' => $lecturer->id]));

        // ---- Catalog Context ----
        $publishers = Publisher::factory()->count(5)->create();
        $authors = Author::factory()->count(12)->create();

        // Kategori hierarkis (self-referencing)
        $tree = [
            'Komputer' => ['Pemrograman', 'Basis Data', 'Jaringan'],
            'Sains' => ['Fisika', 'Matematika'],
            'Sastra' => ['Novel', 'Puisi'],
            'Bisnis' => ['Manajemen', 'Akuntansi'],
        ];

        $categories = collect();
        foreach ($tree as $parentName => $children) {
            $parent = BookCategory::create(['name' => $parentName, 'slug' => str($parentName)->slug()]);
            $categories->push($parent);

            foreach ($children as $childName) {
                $categories->push($parent->children()->create([
                    'name' => $childName,
                    'slug' => str($childName)->slug(),
                ]));
            }
        }

        $books = Book::factory()->count(40)->create()->each(function (Book $book) use ($publishers, $authors, $categories) {
            $book->update(['publisher_id' => $publishers->random()->id]);
            $book->authors()->attach($authors->random(rand(1, 3))->pluck('id')->all());

            // Pivot dengan added_at
            foreach ($categories->random(rand(1, 3)) as $category) {
                $book->categories()->attach($category->id, ['added_at' => now()]);
            }
        });

        // Beberapa buku sengaja habis stok (untuk demo scope available & rollback)
        $books->random(5)->each(fn (Book $b) => $b->update(['stock' => 0]));

        // ---- Circulation Context: beberapa peminjaman lewat service (transaction + event) ----
        $loanService = app(LoanService::class);
        $available = Book::available()->get();

        foreach ($members->take(4) as $member) {
            $loanService->createLoan($member->id, [
                ['book_id' => $available->random()->id, 'quantity' => 1],
            ], 'Data contoh');
        }
    }
}
