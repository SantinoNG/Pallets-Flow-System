<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_name' => fake()->words(2, true),
            'description'  => fake()->sentence(),
            'unit_price'   => fake()->randomFloat(2, 5000, 50000),
        ];
    }
}