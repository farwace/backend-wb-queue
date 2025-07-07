<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Окно приемки - (склад)
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property string $name
 * @property ?string $code
 * @property int $sort
 * @property Table[] $tables
 * @property int $queue_length
 */
class Department extends Model{
    use CrudTrait, LogsActivity;

    protected $table = 'departments';
    protected $guarded = ['id'];

    public function tables():HasMany
    {
        return $this->hasMany(Table::class, 'department_id', 'id');
    }

    public function admins():HasMany
    {
        return $this->hasMany(Admin::class, 'department_id', 'id');
    }
}
