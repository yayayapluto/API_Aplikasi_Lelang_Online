<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\File;
use App\Models\JobType;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate fake but realistic Indonesian-style data
        $kewarganegaraan = $this->faker->randomElement(['WNI', 'WNA']);
        $jenisKelamin = $this->faker->randomElement(['PRIA', 'WANITA']);
        $namaLengkap = $this->faker->name($jenisKelamin === 'PRIA' ? 'male' : 'female');

        // Create fake KTP file
        $fileName = 'files/' . Str::random(20) . '.jpg';
        Storage::disk('public')->put($fileName, 'fake ktp content - ' . $namaLengkap);

        $file = File::query()->create([
            'file_url' => 'storage/' . $fileName,
        ]);

        return [
            'job_type_id' => JobType::query()->pluck("id")->random(),
            'province_id' => Province::query()->pluck("id")->random(),
            'kewarganegaraan' => $kewarganegaraan,
            'nik' => $this->faker->unique()->numerify('################'),
            'nama_lengkap' => $namaLengkap,
            'country_id' => Country::query()->pluck("id")->random(),
            'jenis_kelamin' => $jenisKelamin,
            'city_id' => City::query()->pluck("id")->random(),
            'tempat_lahir' => $this->faker->city,
            'tanggal_lahir' => $this->faker->date('Y-m-d', '2005-01-01'),
            'nomor_telepon' => $this->faker->phoneNumber,
            'alamat' => $this->faker->address,
            'file_id' => $file->id,
            'subdistrict_id' => Subdistrict::query()->pluck("id")->random(),
            'email' => $this->faker->unique()->safeEmail,
            'village_id' => Village::query()->pluck("id")->random(),
            'password' => bcrypt('password'),
        ];
    }
}
