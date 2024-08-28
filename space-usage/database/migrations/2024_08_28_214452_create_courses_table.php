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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('subject_code');
            $table->string('catalog_number');
            $table->string('section');
            $table->string('class_descr')->nullable();
            $table->string('term_code');
            $table->string('term');
            $table->integer('class_duration_weekly');
            $table->integer('duration_minutes');
            $table->string('division');
            $table->string('component_code');
            $table->string('class_nbr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
