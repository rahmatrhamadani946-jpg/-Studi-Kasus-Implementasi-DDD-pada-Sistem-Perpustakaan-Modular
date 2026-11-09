<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Membership\Models\MemberRole;

class MemberRoleFactory extends Factory
{
    protected $model = MemberRole::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => $name,
            'max_loans' => 3,
            'loan_period_days' => 14,
        ];
    }
}
