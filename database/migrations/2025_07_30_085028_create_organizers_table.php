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
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->string("nama_bank");
            $table->string("nomor_telepon")->nullable(); // ini bisa ngambil dari kpknl aja ga sebenernya
            $table->text("alamat")->nullable(); // ini juga bisa ngambil dari kpknl aja ga sebenernya
            $table->string("nama_unit_kerja"); // make sure ini exist di kpknl, dan klo di index eager nya pake query get aja sesuai nama di kpnl.nama, klo di post ga wajib ada di kpknl aja sih
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
