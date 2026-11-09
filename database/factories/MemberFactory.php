<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\MemberRole;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'member_role_id' => MemberRole::factory(),
            'member_number' => 'MBR-' . fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => 'active',
            'active_loans_count' => 0,
            'joined_at' => now(),
            'membership_expires_at' => now()->addYear(),
        ];
    }
}
