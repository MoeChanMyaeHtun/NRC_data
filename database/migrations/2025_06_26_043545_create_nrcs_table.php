<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nrcs', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2);
            $table->string('township_code', 3);
            $table->enum('type', ['N', 'P', 'E']);
            $table->string('number', 6);
            $table->timestamps();
            
            $table->unique(['state_code', 'township_code', 'type', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nrcs');
    }
};
