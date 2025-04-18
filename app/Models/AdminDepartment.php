<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Окно приемки - (склад)
 * @property int $id
 * @property int $admin_id
 * @property int $department_id
 */
class AdminDepartment extends Model{
    use CrudTrait;

    protected $table = 'admin_department';
    protected $guarded = ['id'];

}
