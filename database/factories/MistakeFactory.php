<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mistake>
 */
class MistakeFactory extends Factory
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
            'title' => $this->faker->sentence(),
            'happened_at' => $this->faker->dateTimeBetween('-1 year'),
            'situation' => $this->faker->paragraph(),
            'cause' => $this->faker->paragraph(),
            'my_solution' => $this->faker->paragraph(),
            'ai_notes' => $this->faker->optional()->paragraph(),
            'supplement' => $this->faker->optional()->paragraph(),
            're_ai_notes' => $this->faker->optional()->paragraph(),
            'reminder_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'is_reminded' => $this->faker->boolean(20), // 20%の確率でtrue
        ];
    }
}
