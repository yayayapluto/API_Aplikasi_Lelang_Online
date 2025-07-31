<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId("job_type_id")->nullable()->constrained("job_types")->cascadeOnDelete();
            $table->foreignId("province_id")->nullable()->constrained("provinces")->cascadeOnDelete();
            $table->enum("kewarganegaraan", ["WNA","WNI"]);
            $table->string("nik");
            $table->string("nama_lengkap");
            $table->foreignId("country_id")->nullable()->constrained("countries")->cascadeOnDelete();
            $table->enum("jenis_kelamin", ["PRIA", "WANITA"]);
            $table->foreignId("city_id")->nullable()->constrained("cities")->cascadeOnDelete();
            $table->string("tempat_lahir");
            $table->date("tanggal_lahir");
            $table->string("nomor_telepon");
            $table->text("alamat");
            $table->string("file_ktp");
            $table->foreignId("subdistrict_id")->nullable()->constrained("subdistricts")->cascadeOnDelete();
            $table->string("email")->unique();
            $table->foreignId("village_id")->nullable()->constrained("villages")->cascadeOnDelete();
            $table->string("password");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
