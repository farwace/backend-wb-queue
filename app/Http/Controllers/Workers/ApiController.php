<?php

namespace App\Http\Controllers\Workers;

use App\Events\OrderRequested;
use App\Http\Controllers\Controller;
use App\Http\Resources\QueueResource;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\LoadersSettings;
use App\Models\Queue;
use App\Models\Table;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    use RespondsWithHttpStatus;


    public function auth(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();
        $direction = $arRequest['direction'];

        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode) || strlen($badgeCode) > 7){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }

        $department = Department::query()->where('code', $direction)->first();
        if(!$department){
            return $this->failure('Не удалось определить направление. Обновите страницу и попробуйте еще раз', 422);
        }

        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        $worker->department_id = $department->id;

        if(!$worker){
            $worker = new Worker();
            $worker->code = $badgeCode;
            $worker->name = '';
            $worker->department_id = $department->id;
        }
        $worker->save();

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

    public function leaveTable(Request $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        $worker = Worker::query()->where('code', $badgeCode)->first();
        $worker->department_id = null;
        $worker->save();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 422);
        }
        $table = Table::query()->where('worker_id', $worker->id)->first();
        if(!empty($table)){
            $queue = Queue::query()->where('table_id', $table->id)->where('worker_id', $worker->id)->where('is_closed', false)->first();
            $arItems = Cache::get('checkTables', []);
            $arItems[$table->id] = [
                'id' => $table->id,
                'name' => $table->name,
                'code' => $table->code,
                'workerName' => $worker->name,
                'workerCode' => $worker->code,
            ];

            Cache::put('checkTables', $arItems, 1800);

            if($queue){
                $queue->is_closed = true;
                $queue->save();
            }
            $table->worker_id = null;
            $table->save();
        }
        return $this->success([], 'Success');
    }
    public function selectTable(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();
        $direction = $arRequest['direction'];

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
            return $this->failure('Ошибка получения номера стола!', 422);
        }

        $table = Table::query()->where('id', $arRequest['table_id'])->first();
        if($table && empty($table->worker_id)){
            $table->worker_id = $worker->id;
            $table->save();
            return $this->success($this->workerInfo($worker), 'Success');
        }

        return $this->failure('Не удалось забронировать стол! Обновите страницу и попробуйте еще раз', 422);
    }



    public function enterQueue(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();
        $direction = $arRequest['direction'];

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
        $loaderSettings = LoadersSettings::query()->where('active', true)->orderBy('id', 'asc')->pluck('color')->toArray();
        $lastKey = (int)Cache::get('loaderIndex', 0);
        if(!$loaderSettings){
            $loaderSettings = ['#000000'];
        }
        $lastKey +=1;
        if($lastKey >= (count($loaderSettings))){
            $lastKey = 0;
        }
        Cache::put('loaderIndex', $lastKey);
        $queue = new Queue();
        if(!empty($loaderSettings[$lastKey])){
            $queue->color = $loaderSettings[$lastKey];
        }
        $queue->table_id = $tableId;
        $queue->worker_id = $worker->id;
        $queue->is_closed = false;
        $queue->save();

        $arWorkerInfo['inQueue'] = true;

        event(new OrderRequested($worker->table->department->code, $worker->table->id, false, $worker->table->code, $worker->table->name, $worker->name, $queue->updated_at, $queue->color));
        //OrderRequested::dispatch($queue->id, false, $worker->table->code, $worker->table->name, $worker->name);

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

        $queue = Queue::query()->where('table_id', $tableId)->where('worker_id', $worker->id)->where('is_closed', false)->orderBy('id', 'desc')->first();
        if($queue){
            event(new OrderRequested($worker->table->department->code, $worker->table->id, true, $worker->table->code, $worker->table->name, $worker->name, $queue->updated_at));
            //OrderRequested::dispatch($queue->id, true, $worker->table->code, $worker->table->name, $worker->name);
        }
        Queue::query()->where('table_id', $tableId)->where('worker_id', $worker->id)->update(['is_closed' => true]);

        return $this->success($this->workerInfo($worker), 'Success');
    }


    public function getQueue(?string $direction = 'e1'):JsonResponse
    {
        $queue = Queue::query()->where('is_closed', false)
            ->whereHas('table.department', function ($query) use ($direction) {
                $query->where('code', $direction);
            })
            ->orderBy('id', 'asc')->get();
        return $this->success(QueueResource::collection($queue), 'Success');
    }

    public function getUnavailableTables(?string $direction = 'e1'): JsonResponse
    {
        $fiveHoursAgo = Carbon::now()->subHours(5);

        // Подзапрос: последние закрытые записи по (worker_id, table_id)
        $subQuery = Queue::select(DB::raw('MAX(id) as id'))
            ->where('is_closed', true)
            ->where('created_at', '>=', $fiveHoursAgo)
            ->groupBy('worker_id', 'table_id');

        // Вытащим записи с этими ID
        $lastClosedQueues = Queue::whereIn('id', $subQuery)
            ->with(['worker', 'table']) // подгружаем связи
            ->get();

        // Фильтруем:
        $filtered = $lastClosedQueues->filter(function ($queue) {
            // Исключаем, если есть более новая открытая запись
            $hasNewOpen = Queue::where('worker_id', $queue->worker_id)
                ->where('table_id', $queue->table_id)
                ->where('id', '>', $queue->id)
                ->where('is_closed', false)
                ->exists();

            if ($hasNewOpen) {
                return false;
            }

            // И дополнительно проверяем соответствие связанной таблицы
            $table = $queue->table;
            return $table && $table->worker_id === $queue->worker_id;
        })->values();

        return response()->json(['in_progress' => $filtered, 'closed' => array_values(Cache::get('checkTables', []))]);
    }

    public function setTableChecked(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();
        if(!empty($arRequest['table_id'])){
            $arTables = Cache::get('checkTables', []);
            if(!empty($arTables[$arRequest['table_id']])){
                unset($arTables[$arRequest['table_id']]);
                Cache::put('checkTables', $arTables);
            }
            return $this->success([]);
        }
        return $this->failure('Error!');
    }


    public function getTables(int $departmentId)
    {
        return Table::query()
            ->whereNull('worker_id')
            ->where('department_id', $departmentId)
            ->orderBy('code', 'asc')
            ->get()
            ->toArray();
    }

    public function workerInfo($worker): array
    {
        $departmentId = $worker->department_id ?: 1;

        $table = $worker->table;
        $inQueue = false;
        if(!empty($table)){
            $tableName = $table->name ?: $table->code;

            $queue = Queue::query()->where('table_id', $table->id)->where('worker_id', $worker->id)->where('is_closed', false)->orderBy('id', 'desc')->first();
            if($queue){
                $inQueue = true;
            }
        }
        $arResult = array_merge($worker->toArray(), ['tables' => $this->getTables($departmentId), 'inQueue' => $inQueue]);
        if(!empty($tableName)){
            $arResult = array_merge($arResult, ['table' => $tableName]);
        }

        return $arResult;
    }

}
