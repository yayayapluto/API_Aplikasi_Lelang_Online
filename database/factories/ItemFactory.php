<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bukti_kepemilikan' => $this->faker->randomElement(['Sertifikat', 'Akta Jual Beli', 'Girik']),
            'bukti_kepemilikan_no' => $this->faker->unique()->numerify('BK-########'),
            'bukti_kepemilikan_tgl' => $this->faker->date(),
            'alamat' => $this->faker->address(),
            'luas' => $this->faker->optional()->numberBetween(50, 500),
            'stnk' => $this->faker->optional()->numerify('B #### TEA'),
            'nomor_rangka' => $this->faker->optional()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'nopol' => $this->faker->optional()->numerify('B #### ##'),
            'tahun' => $this->faker->year(),
            'warna' => $this->faker->colorName(),
            'item_type_id' => $this->faker->numberBetween(1, 5),
            'object_type_id' => $this->faker->numberBetween(1, 3),
            'category_id' => $this->faker->numberBetween(1, 4),
        ];
    }
}
