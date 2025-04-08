<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Сотрудник приемки
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?string $name
 * @property string $code
 * @property ?int $department_id
 * @property Department $department
 * @property ?Table $table
 */
class Worker extends Model{
    use CrudTrait;

    protected $table = 'workers';
    protected $guarded = ['id'];

    public function department():HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    public function table():HasOne
    {
        return $this->hasOne(Table::class, 'worker_id', 'id');
    }
}
