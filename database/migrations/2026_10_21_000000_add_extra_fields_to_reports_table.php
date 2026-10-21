<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Дополнительные поля отписки сотрудников:
     *  - name             — имя сотрудника
     *  - neighbor_barcode — «Соседний ШК с этого поступления» (обязательно при ошибке «Нет ШК»)
     *  - bk               — «БК» (обязательно при ошибке «Нет ШК»)
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('name')->nullable()->after('worker');                    // имя сотрудника
            $table->string('neighbor_barcode')->nullable()->after('error_note');    // соседний ШК с поступления
            $table->string('bk')->nullable()->after('neighbor_barcode');            // БК
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['name', 'neighbor_barcode', 'bk']);
        });
    }
};
