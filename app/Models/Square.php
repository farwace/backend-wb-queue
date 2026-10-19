<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Квадрат приема палетов (объединяет несколько рабочих мест/сотрудников)
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?string $name
 * @property string $code
 * @property int $department_id
 * @property int $max_workers
 * @property Department $department
 * @property Worker[] $workers
 */
class Square extends Model{
    use CrudTrait, LogsActivity;

    protected $table = 'squares';
    protected $guarded = ['id'];

    public function department():BelongsTo{
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function workers():HasMany{
        return $this->hasMany(Worker::class, 'square_id', 'id');
    }
}
