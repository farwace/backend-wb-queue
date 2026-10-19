<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Колонка table_id в очереди заказов переименовывается в square_id.
     * FK при переименовании колонки в Postgres следует за ней автоматически.
     */
    public function up(): void
    {
        Schema::table('queue', function ($table) {
            $table->renameColumn('table_id', 'square_id');
        });
    }

    public function down(): void
    {
        Schema::table('queue', function ($table) {
            $table->renameColumn('square_id', 'table_id');
        });
    }
};
