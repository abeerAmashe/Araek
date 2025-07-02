<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('room_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('wood_id')->nullable();
            $table->unsignedBigInteger('fabric_id')->nullable();

            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('wood_id')->references('id')->on('woods')->onDelete('set null');
            $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_details');
    }
}