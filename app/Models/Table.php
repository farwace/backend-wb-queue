<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Стол приема палетов
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?string $name
 * @property string $code
 * @property int $department_id
 * @property Department $department
 * @property ?Worker $worker
 */
class Table extends Model{
    use CrudTrait;

    protected $table = 'tables';
    protected $guarded = ['id'];

    public function department():BelongsTo{
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function worker():BelongsTo{
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }
}
