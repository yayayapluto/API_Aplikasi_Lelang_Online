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
        Schema::create('kpknls', function (Blueprint $table) {
            $table->id();
            $table->string("nama");
            $table->integer("kode_satker")->unique();
            $table->text("alamat");
            $table->string("kota");
            $table->string("provinsi");
            $table->string("nomor_telepon");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpknls');
    }
};
