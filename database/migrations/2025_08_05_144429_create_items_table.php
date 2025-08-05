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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string("bukti_kepemilikan");
            $table->string("bukti_kepemilikan_no");
            $table->date("bukti_kepemilikan_tgl");
            $table->text("alamat");
            $table->string("luas")->nullable();
            $table->string("stnk")->nullable();
            $table->string("nomor_rangka")->nullable();
            $table->string("nopol")->nullable();
            $table->string("tahun")->nullable();
            $table->string("warna")->nullable();
            $table->foreignId("item_type_id")->constrained("item_types")->cascadeOnDelete();
            $table->foreignId("object_type_id")->constrained("object_types")->cascadeOnDelete();
            $table->foreignId("category_id")->constrained("categories")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
