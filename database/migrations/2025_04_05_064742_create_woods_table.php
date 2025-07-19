<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoodsTable extends Migration
{
    public function up()
    {
        Schema::create('woods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('wood_color_id')->constrained()->onDelete('cascade');
            $table->foreignId('wood_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('woods');
    }
}