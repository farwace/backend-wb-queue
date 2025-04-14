<?php

namespace App\Models;

use App\Events\OrderRequested;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use function Psy\debug;

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
 * @property string $color
 * @property ?string $name
 */
class Queue extends Model{
    use CrudTrait;

    protected $table = 'queue';
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::updated(function ($queue) {
            event(new OrderRequested($queue->getAttribute('table')->department_id,$queue->getAttribute('table')->id, $queue->is_closed, $queue->getAttribute('table')->code, $queue->getAttribute('table')->name, $queue->worker->name, $queue->updated_at ));
        });

    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function getCreatedAtForBackpack()
    {
        return Carbon::parse($this->created_at)
            ->addHours(3)
            ->format('d.m.Y H:i');
    }
}
