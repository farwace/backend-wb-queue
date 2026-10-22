<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Гибридное распознавание QR с фото ШК + отметка обработки:
     *  - product_barcode_value / product_barcode_1_value / product_barcode_2_value — коды, распознанные на фронте;
     *  - qr_server_values — JSON с кодами, распознанными сервером (для сверки);
     *  - qr_mismatch — сервер распознал код, отличный от клиентского;
     *  - processed — админ пометил запись как обработанную.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('product_barcode_value')->nullable()->after('product_barcode');   // QR-код с фото (клиент)
            $table->string('product_barcode_1_value')->nullable()->after('product_barcode_1'); // QR-код с фото №1 (клиент)
            $table->string('product_barcode_2_value')->nullable()->after('product_barcode_2'); // QR-код с фото №2 (клиент)
            $table->text('qr_server_values')->nullable()->after('product_barcode_2_value');    // JSON: коды, распознанные сервером
            $table->boolean('qr_mismatch')->default(false)->after('qr_server_values');          // расхождение клиент/сервер
            $table->boolean('processed')->default(false)->after('qr_mismatch');                 // обработано админом
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'product_barcode_value',
                'product_barcode_1_value',
                'product_barcode_2_value',
                'qr_server_values',
                'qr_mismatch',
                'processed',
            ]);
        });
    }
};
