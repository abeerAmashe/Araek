<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomizationsTable extends Migration
{
    public function up()
    {
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('wood_id')->nullable();
            $table->unsignedBigInteger('fabric_id')->nullable();
            $table->float('new_length')->nullable();
            $table->float('new_width')->nullable();
            $table->float('new_height')->nullable();
            $table->float('old_price')->nullable();
            $table->string('wood_color')->nullable();
            $table->string('fabric_color')->nullable();
            $table->float('final_price')->nullable();
            $table->integer('final_time');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->timestamps();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('wood_id')->references('id')->on('woods')->onDelete('set null');
            $table->unsignedBigInteger('wood_type_id')->nullable();
            $table->unsignedBigInteger('wood_color_id')->nullable();
            $table->unsignedBigInteger('fabric_type_id')->nullable();
            $table->unsignedBigInteger('fabric_color_id')->nullable();;
            $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('set null');
            $table->float('fabric_length')->nullable();
            $table->float('fabric_width')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customizations');
    }
}
