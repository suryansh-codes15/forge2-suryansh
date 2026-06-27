<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name' => $name,
            'slug' => \Str::slug($name) . '-' . \Str::random(4),
            'plan' => 'free',
        ];
    }
}
