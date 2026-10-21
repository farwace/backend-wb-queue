<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Отписка (ошибка при приемке)
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Department $department
 * @property ?int $department_id
 * @property ?string $worker         ID сотрудника (номер бейджа)
 * @property ?string $name           Имя сотрудника
 * @property ?string $square         квадрат сотрудника
 * @property ?string $error_type     not_in_order|office_error|no_barcode|other
 * @property ?string $error_note     текст для «другая причина»
 * @property ?string $neighbor_barcode соседний ШК с поступления (для «Нет ШК»)
 * @property ?string $bk             БК (для «Нет ШК»)
 * @property ?string $error_photo    путь к фото для «Нет ШК»
 */
class Report extends Model{
    use CrudTrait;

    /**
     * Возможные типы ошибки: значение => отображаемая подпись.
     */
    public const ERROR_TYPES = [
        'not_in_order' => 'Товар не в заказе',
        'office_error' => 'Ошибка офиса',
        'no_barcode'   => 'Нет ШК',
        'other'        => 'Другая причина',
    ];

    protected $table = 'reports';
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::deleting(function (Report $report) {
            if(!empty($report->error_photo) && Storage::disk('s3')->exists($report->error_photo)){
                Storage::disk('s3')->delete($report->error_photo);
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

    public function exportButtonContent()
    {
        return '<a href="/admin/reports-export" target="_blank" class="btn btn-primary" data-style="zoom-in">
                    <i class="la la-file-csv"></i> <span>Экспорт</span>
                </a>';
    }

    /**
     * Колонка списка: миниатюра фото ошибки (если загружено).
     */
    public function errorPhotoHtml(): string
    {
        if(empty($this->error_photo)){
            return '';
        }
        $url = Storage::disk('s3')->url($this->error_photo);
        $safe = e($url);
        return '<a href="' . $safe . '" target="_blank"><img src="' . $safe . '" height="40" style="border-radius:4px;" alt="фото"></a>';
    }
}
