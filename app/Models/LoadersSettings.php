<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Очередь заказов
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property string $color
 * @property boolean $active
 * @property ?string $name
 * @property int $department_id
 */
class LoadersSettings extends Model{
    use CrudTrait, LogsActivity;

    protected $table = 'loaders_settings';
    protected $guarded = ['id'];

    public function department():BelongsTo{
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
