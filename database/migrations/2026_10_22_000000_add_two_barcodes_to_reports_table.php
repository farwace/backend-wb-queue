<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Новый тип ошибки «Два ШК» (two_barcodes):
     *  - product_barcode_1 — фото «ID (ШК) товара №1»
     *  - product_barcode_2 — фото «ID (ШК) товара №2»
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('product_barcode_1')->nullable()->after('product_barcode'); // фото ШК №1 («Два ШК»)
            $table->string('product_barcode_2')->nullable()->after('product_barcode_1'); // фото ШК №2 («Два ШК»)
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['product_barcode_1', 'product_barcode_2']);
        });
    }
};
