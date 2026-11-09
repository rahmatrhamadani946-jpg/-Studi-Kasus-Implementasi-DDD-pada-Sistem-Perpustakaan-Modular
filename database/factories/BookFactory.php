<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Book;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'publisher_id' => null,
            'title' => rtrim(fake()->sentence(3), '.'),
            'isbn' => $this->validIsbn13(),
            'description' => fake()->paragraph(),
            'published_year' => fake()->numberBetween(1995, 2026),
            'stock' => fake()->numberBetween(1, 10),
        ];
    }

    /** Menghasilkan ISBN-13 acak yang check digit-nya valid. */
    private function validIsbn13(): string
    {
        $first12 = '978' . fake()->unique()->numerify('#########');

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $first12[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return $first12 . ((10 - ($sum % 10)) % 10);
    }
}
