<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Очередь заказов
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property string $color
 * @property boolean $active
 */
class LoadersSettings extends Model{
    use CrudTrait;

    protected $table = 'loaders_settings';
    protected $guarded = ['id'];

}
