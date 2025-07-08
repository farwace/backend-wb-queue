<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * Сотрудник приемки
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Department $department
 * @property ?int $department_id
 * @property ?string $barcode
 * @property ?string $shortage
 * @property ?string $surplus
 * @property ?string $through
 * @property ?string $depersonalization_barcode
 * @property ?string $worker
 * @property ?string $table
 * @property ?string $reason
 * @property ?string $count
 * @property ?array $videos
 * @property ?string $receipts
 * @property ?string $type
 */
class Report extends Model{
    use CrudTrait;

    protected $table = 'reports';
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::deleting(function (Report $report) {
            if(!empty($report->videos && is_array($report->videos))){
                foreach ($report->videos as $filePath) {
                    if ($filePath && Storage::disk('s3')->exists($filePath)) {
                        Storage::disk('s3')->delete($filePath);
                    }
                }
            }
        });
    }

    public function department():BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    protected function videos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($value) ? json_decode($value, true) : '',
            set: fn ($value) => json_encode(!empty($value) ? $value : []),
        );
    }

    public function exportButtonContent()
    {
        return '<a href="/admin/reports-export" target="_blank" class="btn btn-primary" data-style="zoom-in">
                    <i class="la la-file-csv"></i> <span>Экспорт</span>
                </a>';
    }
}
