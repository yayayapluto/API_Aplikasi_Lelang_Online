<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => "Kategori " . $this->faker->unique()->words(2, true),
            "status" => $this->faker->randomElement(["TAYANG", "TIDAK_TAYANG"]),
            "ikon" => "ikon",
            "tipe_ikon" => "md",
            "nama_ikon" => "ikon-keren"
        ];
    }
}
