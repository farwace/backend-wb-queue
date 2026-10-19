<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Переименовываем таблицу столов в квадраты.
     * Сущность «стол» превращается в «квадрат», который обслуживает несколько сотрудников.
     */
    public function up(): void
    {
        Schema::rename('tables', 'squares');
    }

    public function down(): void
    {
        Schema::rename('squares', 'tables');
    }
};
