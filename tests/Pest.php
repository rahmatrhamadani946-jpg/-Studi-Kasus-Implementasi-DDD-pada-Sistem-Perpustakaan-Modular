<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Helper Perpustakaan (Pertemuan 6)
|--------------------------------------------------------------------------
*/

function libraryMember(array $attributes = [], array $role = []): \Modules\Membership\Models\Member
{
    $memberRole = \Modules\Membership\Models\MemberRole::factory()->create($role + [
        'max_loans' => 3,
        'loan_period_days' => 14,
    ]);

    return \Modules\Membership\Models\Member::factory()->create($attributes + [
        'member_role_id' => $memberRole->id,
    ]);
}

function libraryBook(int $stock = 5, array $attributes = []): \Modules\Catalog\Models\Book
{
    return \Modules\Catalog\Models\Book::factory()->create($attributes + ['stock' => $stock]);
}
