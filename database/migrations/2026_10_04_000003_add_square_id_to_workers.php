<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Сотрудник теперь привязан к квадрату (many-to-one: много сотрудников → один квадрат).
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->unsignedBigInteger('square_id')->nullable();
            $table->foreign('square_id')->references('id')->on('squares')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropForeign(['square_id']);
            $table->dropColumn('square_id');
        });
    }
};
