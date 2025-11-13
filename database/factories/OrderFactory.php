<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending', 'send', 'delivered', 'cancel']),
            'total' => $this->faker->randomFloat(2, 50, 2000),
        ];
    }
}
