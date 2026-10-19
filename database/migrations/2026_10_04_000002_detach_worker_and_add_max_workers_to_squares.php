<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Квадрат больше не привязан к одному сотруднику:
     *  - убираем worker_id (связь «1 стол = 1 сотрудник»);
     *  - добавляем настраиваемую вместимость max_workers.
     */
    public function up(): void
    {
        Schema::table('squares', function (Blueprint $table) {
            $table->dropForeign('tables_worker_id_foreign');
            $table->dropColumn('worker_id');
            $table->unsignedInteger('max_workers')->default(10);
        });
    }

    public function down(): void
    {
        Schema::table('squares', function (Blueprint $table) {
            $table->dropColumn('max_workers');
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('set null');
        });
    }
};
