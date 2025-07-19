<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoodTypesTable extends Migration
{
    public function up()
    {
        Schema::create('wood_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_per_meter', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wood_types');
    }
}
