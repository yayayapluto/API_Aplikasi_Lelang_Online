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
        Schema::create('auction_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId("auction_id")->constrained("auctions")->cascadeOnDelete();
            $table->foreignId("item_id")->constrained("items")->cascadeOnDelete();
            $table->foreignId("seller_id")->constrained("sellers")->cascadeOnDelete();
            $table->foreignId("organizer_id")->constrained("organizers")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_contents');
    }
};
