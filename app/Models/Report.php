<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Отписка (ошибка при приемке) — сотрудники
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Department $department
 * @property ?int $department_id
 * @property ?string $worker          ID сотрудника (номер бейджа)
 * @property ?string $name            [не используется в форме] имя сотрудника
 * @property ?string $square          [не используется в форме] квадрат сотрудника
 * @property ?string $error_type      not_in_order|office_error|no_barcode|two_barcodes|other
 * @property ?string $error_note      текст для «Другая причина»
 * @property ?string $product_barcode путь к фото «ID (ШК) товара»
 * @property ?string $product_barcode_1 путь к фото «ID (ШК) товара №1» (для «Два ШК»)
 * @property ?string $product_barcode_2 путь к фото «ID (ШК) товара №2» (для «Два ШК»)
 * @property ?string $product_barcode_value QR-код с фото, распознанный на фронте
 * @property ?string $product_barcode_1_value QR-код с фото №1, распознанный на фронте
 * @property ?string $product_barcode_2_value QR-код с фото №2, распознанный на фронте
 * @property ?string $qr_server_values JSON: коды, распознанные сервером (сверка)
 * @property bool $qr_mismatch расхождение клиентского и серверного QR-кода
 * @property bool $processed пометка админа: запись обработана
 * @property ?string $neighbor_barcode соседний ID (ШК) с поступления (для «Без ID (ШК)»)
 * @property ?string $ttn_type        wb_gi|wb_mp|grid_number
 * @property ?string $ttn_number      номер ТТН (только цифры)
 * @property ?string $product_photo   путь к фото товара
 * @property ?string $office_id_photo путь к фото «Ошибка с ТСД»
 */
class Report extends Model{
    use CrudTrait;

    /**
     * Возможные типы ошибки: значение => отображаемая подпись.
     */
    public const ERROR_TYPES = [
        'not_in_order'   => 'Товар не в заказе',
        'office_error'   => 'Неверный офис',
        'no_barcode'     => 'Без ID (ШК)',
        'two_barcodes'   => 'Два ШК',
        'other'          => 'Другая причина',
    ];

    /**
     * Варианты ТТН: значение => отображаемая подпись.
     */
    public const TTN_TYPES = [
        'wb_gi'       => 'WB-GI',
        'wb_mp'       => 'WB-MP',
        'grid_number' => 'Номер сетки',
    ];

    protected $table = 'reports';
    protected $guarded = ['id'];

    protected $casts = [
        'qr_mismatch' => 'boolean',
        'processed' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleting(function (Report $report) {
            foreach (['product_barcode', 'product_barcode_1', 'product_barcode_2', 'product_photo', 'office_id_photo'] as $field) {
                if (!empty($report->$field) && Storage::disk('s3')->exists($report->$field)) {
                    Storage::disk('s3')->delete($report->$field);
                }
            }
        });
    }

    public function department():BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Человекочитаемая подпись выбранного типа ошибки.
     */
    public function errorTypeLabel(): string
    {
        return self::ERROR_TYPES[$this->error_type] ?? (string)$this->error_type;
    }

    /**
     * Человекочитаемая подпись выбранного типа ТТН.
     */
    public function ttnTypeLabel(): string
    {
        return self::TTN_TYPES[$this->ttn_type] ?? (string)$this->ttn_type;
    }

    public function exportButtonContent()
    {
        return '<a href="/admin/reports-export" target="_blank" class="btn btn-primary" data-style="zoom-in">
                    <i class="la la-file-csv"></i> <span>Экспорт</span>
                </a>';
    }

    /**
     * Отрисовка миниатюры фото по пути в s3 (пустая строка, если фото нет).
     */
    private function photoHtml(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        $url = Storage::disk('s3')->url($path);
        $safe = e($url);
        return '<a href="' . $safe . '" target="_blank"><img src="' . $safe . '" height="40" style="border-radius:4px;" alt="фото"></a>';
    }

    /**
     * Колонка списка: миниатюра фото «ID (ШК) товара» + распознанный QR-код.
     */
    public function productBarcodePhotoHtml(): string
    {
        return $this->photoHtml($this->product_barcode) . $this->qrValueHtml($this->product_barcode_value);
    }

    /**
     * Колонка списка: миниатюра фото «ID (ШК) товара №1» («Два ШК») + QR-код.
     */
    public function productBarcode1PhotoHtml(): string
    {
        return $this->photoHtml($this->product_barcode_1) . $this->qrValueHtml($this->product_barcode_1_value);
    }

    /**
     * Колонка списка: миниатюра фото «ID (ШК) товара №2» («Два ШК») + QR-код.
     */
    public function productBarcode2PhotoHtml(): string
    {
        return $this->photoHtml($this->product_barcode_2) . $this->qrValueHtml($this->product_barcode_2_value);
    }

    /**
     * Текст распознанного QR-кода под миниатюрой фото.
     */
    private function qrValueHtml(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        return '<div class="small"><code>' . e($value) . '</code></div>';
    }

    /**
     * Колонка/детали: предупреждение о расхождении клиентского и серверного QR-кода.
     */
    public function qrMismatchHtml(): string
    {
        if (!$this->qr_mismatch) {
            return '';
        }
        $server = json_decode((string)$this->qr_server_values, true) ?: [];
        $serverText = implode(', ', array_map(fn ($v) => (string)$v, $server));
        return '<span class="text-warning fw-bold">⚠️ Расхождение ШК!</span>'
            . '<div class="small">Сервер: <code>' . e($serverText) . '</code></div>';
    }

    /**
     * Колонка списка: миниатюра «Фото товара».
     */
    public function productPhotoHtml(): string
    {
        return $this->photoHtml($this->product_photo);
    }

    /**
     * Колонка списка: миниатюра фото «ID Офиса».
     */
    public function officeIdPhotoHtml(): string
    {
        return $this->photoHtml($this->office_id_photo);
    }
}
