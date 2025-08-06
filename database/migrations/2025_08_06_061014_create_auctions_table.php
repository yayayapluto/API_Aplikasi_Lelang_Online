k<?php

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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string("nama_lot")->unique();
            $table->bigInteger("nilai_limit");
            $table->bigInteger("nilai_jaminan");
            $table->string("kode_lot")->unique();
            $table->date("tanggal_batas_jaminan");
            $table->foreignId("province_id")->constrained("provinces")->cascadeOnDelete();
            $table->foreignId("kpknl_id")->constrained("kpknls")->cascadeOnDelete();
            $table->date("tanggal_mulai");
            $table->date("tanggal_selesai");
            $table->enum("status", ["TAYANG", "SELESAI"]);
            $table->string("cara_penawaran");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
