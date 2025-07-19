<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomizationItemsTable extends Migration
{
    public function up()
    {
        Schema::create('customization_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_customization_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('new_length', 8, 2)->default(0);
            $table->decimal('new_width', 8, 2)->default(0);
            $table->decimal('new_height', 8, 2)->default(0);
            $table->decimal('fabric_length', 8, 2)->default(0);
            $table->decimal('fabric_width', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customization_items');
    }
}
