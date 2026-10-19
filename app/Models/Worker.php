<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Сотрудник приемки
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?string $name
 * @property string $code
 * @property ?int $department_id
 * @property ?int $square_id
 * @property Department $department
 * @property ?Square $square
 */
class Worker extends Model{
    use CrudTrait, LogsActivity;

    protected $table = 'workers';
    protected $guarded = ['id'];

    public function department():BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function square():BelongsTo
    {
        return $this->belongsTo(Square::class, 'square_id', 'id');
    }
}
