<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('cart_item_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');           
            $table->unsignedBigInteger('item_id');           
            $table->unsignedInteger('count_reserved');     
            $table->timestamps();
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->index(['cart_id', 'item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_item_reservations');
    }
}