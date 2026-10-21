<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Переработка полей отписки сотрудников под новые сценарии ошибок:
     *  - product_barcode  — ID (ШК) товара (для «Товар не в заказе» / «Неверный офис»)
     *  - ttn_type         — тип ТТН: wb_gi|wb_mp|grid_number
     *  - ttn_number       — номер ТТН (только цифры)
     *  - product_photo    — «Фото товара»
     *  - office_id_photo  — фото «ID Офиса»
     * Удаляются неиспользуемые: bk, error_photo (заменён на product_photo).
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('product_barcode')->nullable()->after('error_note');   // ID (ШК) товара
            $table->string('ttn_type')->nullable()->after('product_barcode');      // wb_gi|wb_mp|grid_number
            $table->string('ttn_number')->nullable()->after('ttn_type');           // номер ТТН (цифры)
            $table->string('product_photo')->nullable()->after('ttn_number');      // фото товара
            $table->string('office_id_photo')->nullable()->after('product_photo'); // фото ID офиса
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['bk', 'error_photo']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['product_barcode', 'ttn_type', 'ttn_number', 'product_photo', 'office_id_photo']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('bk')->nullable();
            $table->string('error_photo')->nullable();
        });
    }
};
