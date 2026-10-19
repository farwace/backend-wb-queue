<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Отписки переделаны под новые поля: ID сотрудника, квадрат сотрудника и тип ошибки
     * (+ опциональные фото/текст). Старые колонки (ШК, недостача, излишек и т.п.) удаляются.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('square')->nullable()->after('worker');                 // квадрат сотрудника
            $table->string('error_type')->nullable()->after('square');              // not_in_order|office_error|no_barcode|other
            $table->string('error_note')->nullable()->after('error_type');          // текст для «другая причина»
            $table->string('error_photo')->nullable()->after('error_note');         // фото для «Нет ШК»
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'barcode',
                'shortage',
                'surplus',
                'through',
                'depersonalization_barcode',
                'table',
                'reason',
                'count',
                'videos',
                'receipts',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['square', 'error_type', 'error_note', 'error_photo']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('barcode')->nullable();
            $table->string('shortage')->nullable();
            $table->string('surplus')->nullable();
            $table->string('through')->nullable();
            $table->string('depersonalization_barcode')->nullable();
            $table->string('table')->nullable();
            $table->string('reason')->nullable();
            $table->string('count')->nullable();
            $table->text('videos')->nullable();
            $table->string('receipts')->nullable();
            $table->string('type')->nullable();
        });
    }
};
