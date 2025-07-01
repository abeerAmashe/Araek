<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFabricsTable extends Migration
{
    public function up()
    {
        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_per_meter', 10, 2);
            $table->foreignId('fabric_color_id')->constrained()->onDelete('cascade');
            $table->foreignId('fabric_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fabrics');
    }
}