<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * Инцидент
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Department $department
 * @property ?int $department_id
 * @property ?array $attachments
 * @property ?string $worker_name
 * @property ?string $worker_code
 * @property ?string $message
 * @property ?string $type
 */
class Incident extends Model{
    use CrudTrait;

    protected $table = 'incidents';
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::deleting(function (Incident $incident) {
            if(!empty($incident->attachments && is_array($incident->attachments))){
                foreach ($incident->attachments as $filePath) {
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

    protected function attachments(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($value) ? json_decode($value, true) : '',
            set: fn ($value) => json_encode(!empty($value) ? $value : []),
        );
    }


    public function exportButtonContent()
    {
        return '<a href="/admin/incidents-export" target="_blank" class="btn btn-primary" data-style="zoom-in">
                    <i class="la la-file-csv"></i> <span>Экспорт</span>
                </a>';
    }
}
