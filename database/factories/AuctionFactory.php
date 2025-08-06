<?php

namespace Database\Factories;

use App\Models\Kpknl;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuctionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $namaLot = $this->faker->unique()->sentence(3);

        return [
            'nama_lot' => $namaLot,
            'kode_lot' => strtoupper(Str::random(6)),
            'nilai_limit' => $this->faker->numberBetween(1000000, 1000000000),
            'nilai_jaminan' => $this->faker->numberBetween(500000, 50000000),
            'tanggal_batas_jaminan' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'province_id' => Province::query()->pluck("id")->random(),
            'kpknl_id' => Kpknl::query()->pluck("id")->random(),
            'tanggal_mulai' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'tanggal_selesai' => $this->faker->dateTimeBetween('+3 weeks', '+4 weeks'),
            'status' => $this->faker->randomElement(['TAYANG', 'SELESAI']),
            'cara_penawaran' => $this->faker->randomElement(['Lelang Online', 'Lelang Langsung', 'Lelang Gabungan']),
        ];
    }
}
