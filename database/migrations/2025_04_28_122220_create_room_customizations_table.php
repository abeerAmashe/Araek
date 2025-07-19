<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomCustomizationsTable extends Migration
{
    public function up()
    {
        Schema::create('room_customizations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');

    $table->foreignId('wood_type_id')->constrained('wood_types')->onDelete('restrict');
    $table->foreignId('wood_color_id')->constrained('wood_colors')->onDelete('restrict');
            
    $table->foreignId('fabric_type_id')->constrained('fabric_types')->onDelete('restrict');
    $table->foreignId('fabric_color_id')->constrained('fabric_colors')->onDelete('restrict');

    $table->decimal('final_price', 10, 2);
    $table->integer('final_time');
    $table->timestamps();
});

    }

    public function down()
    {
        Schema::dropIfExists('room_customizations');
    }
}