<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
        
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('image_url')->nullable();
            $table->integer('count_reserved')->default(0);
            $table->decimal('time')->default(0);
            $table->decimal('price')->default(0);
            $table->integer('count')->default(1);
            $table->integer('wood_type_id');
            $table->integer('wood_color_id');
            $table->integer('fabric_type_id');
            $table->integer('fabric_color_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};