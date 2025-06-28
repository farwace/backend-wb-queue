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
        Schema::create('reply', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('reason')->nullable();
            $table->string('receiptNumber')->nullable();
            $table->string('vehicleNumber')->nullable();
            $table->string('gateNumbers')->nullable();
            $table->text('videos')->nullable();
            $table->timestamps();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('receipts');
            $table->string('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reply');
    }
};
