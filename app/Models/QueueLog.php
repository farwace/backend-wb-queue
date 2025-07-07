<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Логи
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?int $department_id
 * @property Department $department
 * @property ?string $status
 * @property ?string $message
 * @property ?string $table
 * @property ?string $worker_badge
 * @property ?string $worker_name
 */
class QueueLog extends Model{
    use CrudTrait, LogsActivity;

    protected $table = 'queue_logs';
    protected $guarded = ['id'];

    public function department():BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
