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

            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('wood_id')->nullable();
            $table->unsignedBigInteger('fabric_id')->nullable();
            $table->unsignedBigInteger('customer_id');

            $table->decimal('new_length', 8, 2)->nullable();
            $table->decimal('new_width', 8, 2)->nullable();
            $table->decimal('new_height', 8, 2)->nullable();

            $table->decimal('old_price', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);

            $table->string('wood_color')->nullable();
            $table->string('fabric_color')->nullable();

            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('wood_id')->references('id')->on('woods')->onDelete('set null');
            $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customizations');
    }
}