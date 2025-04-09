<?php

namespace App\Http\Controllers\Workers;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Queue;
use App\Models\Table;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use RespondsWithHttpStatus;


    public function auth(Request $request): JsonResponse
    {

        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            $worker = new Worker();
            $worker->code = $badgeCode;
            $worker->name = '';
            $worker->save();
        }

        return $this->success($this->workerInfo($worker), 'Success');

    }

    public function update(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();

        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 422);
        }

        if(empty($arRequest['name'])){
            return $this->failure('Не удалось сохранить имя', 422);
        }

        $worker->name = $arRequest['name'];
        $worker->save();

        return $this->success($this->workerInfo($worker), 'Success');

    }

    public function selectTable(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();

        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 422);
        }

        if(empty($arRequest['table_id'])){
            return $this->failure('Ошибка получения номера столика!', 422);
        }

        $table = Table::query()->where('id', $arRequest['table_id'])->first();
        if($table && empty($table->worker_id)){
            $table->worker_id = $worker->id;
            $table->save();
            return $this->success($this->workerInfo($worker), 'Success');
        }

        return $this->failure('Не удалось забронировать столик! Обновите страницу и попробуйте еще раз', 422);
    }



    public function enterQueue(Request $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 422);
        }

        $tableId = $worker->table->id;

        $arWorkerInfo = $this->workerInfo($worker);
        if(!empty($arWorkerInfo['inQueue'])){
            return $this->failure('Вы уже на очереди!', 422);
        }

        $queue = new Queue();
        $queue->table_id = $tableId;
        $queue->worker_id = $worker->id;
        $queue->is_closed = false;
        $queue->save();

        $arWorkerInfo['inQueue'] = true;

        return $this->success($arWorkerInfo, 'Success');
    }

    public function receiveItem(Request $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 422);
        }

        $tableId = $worker->table->id;

        Queue::query()->where('table_id', $tableId)->where('worker_id', $worker->id)->update(['is_closed' => true]);

        return $this->success($this->workerInfo($worker), 'Success');
    }




    public function getTables()
    {
        return Table::query()->whereNull('worker_id')->orderBy('code', 'asc')->get()->toArray();
    }

    public function workerInfo($worker): array
    {
        $table = $worker->table;
        $inQueue = false;
        if(!empty($table)){
            $tableName = $table->name ?: $table->code;

            $queue = Queue::query()->where('table_id', $table->id)->where('worker_id', $worker->id)->where('is_closed', false)->orderBy('id', 'desc')->first();
            if($queue){
                $inQueue = true;
            }
        }
        $arResult = array_merge($worker->toArray(), ['tables' => $this->getTables(), 'inQueue' => $inQueue]);
        if(!empty($tableName)){
            $arResult = array_merge($arResult, ['table' => $tableName]);
        }

        return $arResult;
    }

}
