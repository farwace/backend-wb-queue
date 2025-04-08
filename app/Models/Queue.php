<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Очередь заказов
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property boolean $is_closed
 * @property int $worker_id
 * @property int $table_id
 * @property Worker $worker
 * @property Table $table
 */
class Queue extends Model{
    use CrudTrait;

    protected $table = 'queue';
    protected $guarded = ['id'];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }
}
