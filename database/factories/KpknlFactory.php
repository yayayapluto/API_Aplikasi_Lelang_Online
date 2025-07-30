<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kpknl>
 */
class KpknlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => "KPKNL " . $this->faker->unique()->words(3, true),
            "kode_satker" => $this->faker->numerify("######"),
            "alamat" => $this->faker->address(),
            "kota" => City::query()->pluck("nama")->random(),
            "provinsi" => Province::query()->pluck("nama")->random(),
            "nomor_telepon" => $this->faker->phoneNumber()
        ];
    }
}
