<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('wood_id')->constrained('woods')->onDelete('cascade');
            $table->foreignId('fabric_id')->constrained()->onDelete('cascade');
            $table->float('wood_length')->nullable();
            $table->float('wood_width')->nullable();
            $table->float('wood_height')->nullable();
            $table->float('fabric_length')->nullable();
            $table->float('fabric_width')->nullable();
            $table->float('fabric_dimension')->nullable();
            $table->float('wood_area_m2')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('item_details');
    }
}