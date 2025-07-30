<?php

namespace Database\Factories;

use App\Models\Kpknl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama_bank" => "Bank " . $this->faker->word(),
            "nomor_telepon" => $this->faker->phoneNumber(),
            "alamat" => $this->faker->address(),
            "nama_unit_kerja" => $this->faker->randomElement(["Unit " . $this->faker->word(), Kpknl::query()->pluck("nama")->random()])
        ];
    }
}
