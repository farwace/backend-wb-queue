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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->timestamps();
            $table->string('barcode')->nullable();
            $table->string('shortage')->nullable();
            $table->string('surplus')->nullable();
            $table->string('through')->nullable();
            $table->string('depersonalization_barcode')->nullable();
            $table->string('worker')->nullable();
            $table->string('table')->nullable();
            $table->string('reason')->nullable();
            $table->string('count')->nullable();
            $table->text('videos')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
