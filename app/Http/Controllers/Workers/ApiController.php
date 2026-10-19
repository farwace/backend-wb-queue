<?php

namespace App\Http\Controllers\Workers;

use App\Events\OrderRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitLeaveTableRequest;
use App\Http\Requests\SubmitReceiveItemRequest;
use App\Http\Resources\QueueResource;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\Incident;
use App\Models\LoadersSettings;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Square;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        if($worker){
            $worker->department_id = $department->id;
            $worker->save();
        }

        if(!$worker){
            $worker = new Worker();
            $worker->code = $badgeCode;
            $worker->name = '';
            $worker->department_id = $department->id;
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
            return $this->failure('Сотрудник не найден!', 403,403);
        }

        if(empty($arRequest['name'])){
            return $this->failure('Не удалось сохранить имя', 422);
        }

        $worker->name = $arRequest['name'];
        $worker->save();

        return $this->success($this->workerInfo($worker), 'Success');

    }

    public function isPhotoRequired(Request $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }

        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 403,403);
        }

        if(empty($worker->square)){
            return $this->failure('Отсутствует привязка сотрудника к квадрату', 422, 422);
        }

        if(empty($worker->square->department)){
            return $this->failure('Отсутствует привязка сотрудника к направлению', 422, 422);
        }

        return $this->success(!!$worker->square->department->photo_required);
    }

    public function leaveTable(SubmitLeaveTableRequest $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 403,403);
        }

        $square = $worker->square;
        $squareName = !empty($square) ? ($square->name ?: ($square->code ?: '')) : '';
        $departmentId = !empty($square) ? ($square->department_id ?: $worker->department_id) : $worker->department_id;

        if(!empty($square)){
            $queue = Queue::query()->where('square_id', $square->id)->where('worker_id', $worker->id)->where('is_closed', false)->first();
            if(!empty($queue)){
                $queueLog = new QueueLog();
                $queueLog->worker_badge = $worker->code;
                $queueLog->worker_name = $worker->name;
                $queueLog->table = $squareName;
                $queueLog->department_id = $departmentId;
                $queueLog->status = 'warning_logout';
                $queueLog->message = 'Попытался выйти из аккаунта во время ожидания палета!';
                $queueLog->save();
                return $this->failure('Замечена подозрительная активность! При повторе информация будет отправлена старшему!');
            }
        }

        $photoRequired = !empty($square?->department?->photo_required);

        if (empty($request->validated('is_admin'))) {

            $arAttachments = $request->validated('attachments');
            if (count($arAttachments ?: []) < 1 && $photoRequired) {
                return $this->failure('Прикрепите фото');
            }
            if (empty($square?->department?->code)) {
                return $this->failure('Утерена привязка к направлению - обратитесь к старшему');
            }

            $arPath = [];
            if (!empty($arAttachments)) {
                /** @var UploadedFile $file */
                foreach ($arAttachments as $file) {
                    $path = $file->store('incidents/' . $square->department->code . '/' . now()->format('Y-m-d'), 's3');
                    $arPath[] = $path;
                }
            }

            $incident = new Incident();
            $incident->attachments = $arPath;
            $incident->type = 'Выход';
            $incident->message = '';
            $incident->department_id = $square->department_id;
            $incident->worker_name = $worker->name ?: '';
            $incident->worker_code = $badgeCode;
            $incident->save();
        }

        if(!empty($square)){
            $queue = Queue::query()->where('square_id', $square->id)->where('worker_id', $worker->id)->where('is_closed', false)->first();
            $arItems = Cache::get('checkTables', []);
            $arItems[$worker->id] = [
                'id' => $worker->id,
                'squareId' => $square->id,
                'name' => $square->name,
                'code' => $square->code,
                'workerName' => $worker->name,
                'workerCode' => $worker->code,
                'timestamp' => Carbon::now(),
                'departmentId' => $square->department_id,
            ];

            Cache::put('checkTables', $arItems, 1800);

            if($queue){
                event(new OrderRequested($square->department->code, $queue->id, true, $square->code, $square->name, $worker->name, $queue->updated_at, $queue->color, $queue->name));
                $queue->is_closed = true;
                $queue->save();
            }
        }

        $worker->square_id = null;
        $worker->department_id = null;
        $worker->save();

        $queueLog = new QueueLog();
        $queueLog->worker_badge = $worker->code;
        $queueLog->worker_name = $worker->name;
        $queueLog->table = $squareName;
        $queueLog->department_id = $departmentId;
        $queueLog->status = 'logout';
        $queueLog->message = 'Вышел из аккаунта';
        $queueLog->save();

        return $this->success([], 'Success');
    }
    public function selectSquare(Request $request): JsonResponse
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
            return $this->failure('Сотрудник не найден!', 403,403);
        }

        if(empty($arRequest['square_id'])){
            return $this->failure('Ошибка получения номера квадрата!', 422);
        }

        $squareId = (int)$arRequest['square_id'];

        // Атомарная проверка вместимости: блокируем строку квадрата, чтобы сериализовать параллельный выбор
        $result = DB::transaction(function () use ($worker, $squareId) {
            /** @var ?Square $square */
            $square = Square::query()->where('id', $squareId)->lockForUpdate()->first();
            if(!$square){
                return ['ok' => false, 'msg' => 'Не удалось забронировать квадрат! Обновите страницу и попробуйте еще раз'];
            }
            $occupied = Worker::query()->where('square_id', $square->id)->where('id', '!=', $worker->id)->count();
            if($occupied >= (int)$square->max_workers){
                return ['ok' => false, 'msg' => 'В этом квадрате нет свободных мест! Выберите другой квадрат'];
            }
            $worker->square_id = $square->id;
            $worker->save();
            return ['ok' => true];
        });

        if(!$result['ok']){
            return $this->failure($result['msg'], 422);
        }

        /** @var Square $square */
        $square = Square::query()->find($squareId);

        $queueLog = new QueueLog();
        $queueLog->worker_badge = $worker->code;
        $queueLog->worker_name = $worker->name;
        $queueLog->table = $square->name ?: $square->code;
        $queueLog->department_id = $square->department_id;
        $queueLog->status = 'login';
        $queueLog->message = 'Занял квадрат';
        $queueLog->save();

        return $this->success($this->workerInfo($worker), 'Success');
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
            return $this->failure('Сотрудник не найден!', 403, 403);
        }

        if(empty($worker->square->id)){
            return $this->failure('Ошибка авторизации! Обновите страницу', 422);
        }
        $squareId = $worker->square->id;

        if(empty($worker->square->department->code)){
            return $this->failure('Утеряно направление (Ссылка: '. $direction .'), Обратитесь к старшему!', 422);
        }
        if($worker->square->department->code !== $direction){
            return $this->failure('Ошибка направления (Ссылка: '. $direction .', в системе: ' . $worker->square->department->code . ' ), Обратитесь к старшему!', 422);
        }

        $arWorkerInfo = $this->workerInfo($worker);
        if(!empty($arWorkerInfo['inQueue'])){
            return $this->failure('Вы уже на очереди!', 422);
        }
        $loaderSettings = LoadersSettings::query()
            ->where('active', true)
            ->where('department_id', $worker->square->department_id)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        $lastKey = (int)Cache::get('loaderIndex-' . $worker->square->department_id, 0);
        if(!$loaderSettings){
            $loaderSettings = [
                [
                    'color' => '#000000',
                    'name' => ''
                ]
            ];
        }
        $lastKey +=1;
        if($lastKey >= (count($loaderSettings))){
            $lastKey = 0;
        }
        Cache::put('loaderIndex-' . $worker->square->department_id, $lastKey);
        $queue = new Queue();
        if(!empty($loaderSettings[$lastKey])){
            $queue->color = $loaderSettings[$lastKey]['color'];
            $queue->name = $loaderSettings[$lastKey]['name'];
        }
        $queue->square_id = $squareId;
        $queue->worker_id = $worker->id;
        $queue->is_closed = false;
        $queue->save();

        $arWorkerInfo['inQueue'] = true;

        event(new OrderRequested($worker->square->department->code, $queue->id, false, $worker->square->code, $worker->square->name, $worker->name, $queue->updated_at, $queue->color, $queue->name));
        $queueLog = new QueueLog();
        $queueLog->worker_badge = $worker->code;
        $queueLog->worker_name = $worker->name;
        $queueLog->table = $worker->square->name ?: $worker->square->code;
        $queueLog->department_id = $worker->square->department_id;
        $queueLog->status = 'success';
        $queueLog->message = 'Заказал товар!';
        $queueLog->save();

        return $this->success($arWorkerInfo, 'Success');
    }

    public function receiveItem(SubmitReceiveItemRequest $request): JsonResponse
    {
        $badgeCode = $request->headers->get('badge-code');
        if(empty($badgeCode) || !is_numeric($badgeCode)){
            return $this->failure('Не удалось корректно обработать код сотрудника', 422);
        }
        /** @var ?Worker $worker */
        $worker = Worker::query()->where('code', $badgeCode)->first();
        if(!$worker){
            return $this->failure('Сотрудник не найден!', 403,403);
        }

        if(empty($worker->square->id)){
            return $this->failure('Ошибка авторизации! Обновите страницу', 422);
        }
        $squareId = $worker->square->id;

        $queue = Queue::query()->where('square_id', $squareId)->where('worker_id', $worker->id)->where('is_closed', false)->orderBy('id', 'desc')->first();

        if($queue){
            if(abs(Carbon::now()->diffInSeconds($queue->created_at)) < 55){
                $queueLog = new QueueLog();
                $queueLog->worker_badge = $worker->code;
                $queueLog->worker_name = $worker->name;
                $queueLog->table = $worker->square->name ?: $worker->square->code;
                $queueLog->department_id = $worker->square->department_id;
                $queueLog->status = 'error';
                $queueLog->message = 'Нажал получить товар слишком быстро (' . abs(Carbon::now()->diffInSeconds($queue->created_at)) . ' сек)!';
                $queueLog->save();

                return $this->failure('Замечена подозрительная активность! При повторе информация будет отправлена старшему!', 422);
            }

        }

        $photoRequired = !empty($worker->square->department->photo_required);

        if (empty($request->validated('is_admin'))) {
            $arAttachments = $request->validated('attachments');
            if (count($arAttachments ?: []) < 1 && $photoRequired) {
                return $this->failure('Прикрепите фото');
            }
            if (empty($worker->square->department->code)) {
                return $this->failure('Утерена привязка к направлению - обратитесь к старшему');
            }
            $msg = $request->validated('message');
            if(empty($msg)){
                return $this->failure('Укажите номер короба');
            }

            $arPath = [];
            if (!empty($arAttachments)) {
                /** @var UploadedFile $file */
                foreach ($arAttachments as $file) {
                    $path = $file->store('incidents/' . $worker->square->department->code . '/' . now()->format('Y-m-d'), 's3');
                    $arPath[] = $path;
                }
            }

            $incident = new Incident();
            $incident->attachments = $arPath;
            $incident->type = 'Товар получен';
            $incident->message = $msg ?: '';
            $incident->department_id = $worker->square->department_id;
            $incident->worker_name = $worker->name ?: '';
            $incident->worker_code = $badgeCode;
            $incident->save();
        }

        if($queue){
            event(new OrderRequested($worker->square->department->code, $queue->id, true, $worker->square->code, $worker->square->name, $worker->name, $queue->updated_at));
        }

        /** @var Queue $queue */
        $queue = Queue::query()->where('square_id', $squareId)->where('worker_id', $worker->id)->where('is_closed', false)->first();

        if(!empty($queue->color)){
            /** @var Queue $firstQueue */
            $firstQueue = Queue::query()
                ->where('color', $queue->color)
                ->where('name', $queue->name)
                ->where('is_closed', false)
                ->orderBy('id', 'asc')
                ->first();
        }

        if(!empty($queue)){
            $queue->is_closed = true;
            $queue->save();
        }

        $status = 'success';
        $message = 'Получил товар!';
        if(!empty($firstQueue) && !empty($queue)){
            if($firstQueue->id != $queue->id){
                $status = 'warning';
                $message = 'Получил товар, НО раньше очереди';
                if(!empty($queue->name)){
                    $message .= ' (грузчик ' . $queue->name;
                    if(!empty($firstQueue->worker)){
                        $message .= ' -> [' . $firstQueue->worker->code . '] ' . $firstQueue->worker->name;
                    }
                    $message .= ')';
                }
            }
        }

        $queueLog = new QueueLog();
        $queueLog->worker_badge = $worker->code;
        $queueLog->worker_name = $worker->name;
        $queueLog->table = $worker->square->name ?: $worker->square->code;
        $queueLog->department_id = $worker->square->department_id;
        $queueLog->status = $status;
        $queueLog->message = $message;
        $queueLog->save();

        return $this->success($this->workerInfo($worker), 'Success');
    }


    public function getQueue(Request $request, ?string $direction = 'e1'):JsonResponse
    {
        if(config('app.password-required', false)){
            $pass = $request->headers->get('Auth-string');
            $dep = Department::query()->where('code', $direction)->first();
            if(!empty($dep)){
                if($dep->password !== $pass){
                    return $this->failure('Неверный пароль!', 401, 401);
                }
            }
        }

        $queue = Queue::query()->where('is_closed', false)
            ->whereHas('square.department', function ($query) use ($direction) {
                $query->where('code', $direction);
            })
            ->orderBy('id', 'asc')->get();
        return $this->success(QueueResource::collection($queue), 'Success');
    }

    public function getUnavailableTables(?string $direction = 'e1'): JsonResponse
    {
        $fiveHoursAgo = Carbon::now()->subHours(5);

        // Подзапрос: последние закрытые записи по (worker_id, square_id)
        $subQuery = Queue::select(DB::raw('MAX(id) as id'))
            ->where('is_closed', true)
            ->where('created_at', '>=', $fiveHoursAgo)
            ->groupBy('worker_id', 'square_id');

        // Вытащим записи с этими ID
        $lastClosedQueues = Queue::whereIn('id', $subQuery)
            ->with(['worker', 'square', 'square.department']) // подгружаем связи
            ->get();

        // Фильтруем:
        $filtered = $lastClosedQueues->filter(function ($queue) {
            // Исключаем, если есть более новая открытая запись
            $hasNewOpen = Queue::where('worker_id', $queue->worker_id)
                ->where('square_id', $queue->square_id)
                ->where('id', '>', $queue->id)
                ->where('is_closed', false)
                ->exists();

            if ($hasNewOpen) {
                return false;
            }

            // Сотрудник всё ещё привязан к этому квадрату
            return $queue->worker && $queue->worker->square_id === $queue->square_id;
        })->values();

        return response()->json(['in_progress' => $filtered, 'closed' => array_values(Cache::get('checkTables', []))]);
    }

    public function setTableChecked(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();
        $workerId = $arRequest['worker_id'] ?? $arRequest['table_id'] ?? null;
        if(!empty($workerId)){
            $arItems = Cache::get('checkTables', []);
            if(!empty($arItems[$workerId])){
                unset($arItems[$workerId]);
                Cache::put('checkTables', $arItems);
            }
            return $this->success([]);
        }
        return $this->failure('Error!');
    }

    /** @deprecated  */
    public function getDepartmentList()
    {
        return response()->json(Department::query()->orderBy('code', 'asc')->get());
    }

    public function getParamValue(string $paramName)
    {
        $val = null;
        if(config()->has($paramName)){
            $val = config()->get($paramName);
        }
        return response()->json($val);
    }

    public function getDepartmentTablesLength(?string $direction = 'e1')
    {
        $cnt = 1;
        $dep = Department::query()->where('code', $direction)->first();
        if(!empty($dep)){
            if(!empty($dep->queue_length)){
                if($dep->queue_length >= $cnt){
                    $cnt = $dep->queue_length;
                }
            }
        }
        return $cnt;
    }

    public function getSquares(int $departmentId)
    {
        return Square::query()
            ->withCount('workers')
            ->where('department_id', $departmentId)
            ->orderBy('code', 'asc')
            ->get()
            ->map(function ($s){
                $max = (int)$s->max_workers;
                $occupied = (int)$s->workers_count;
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'max_workers' => $max,
                    'occupied' => $occupied,
                    'free' => max(0, $max - $occupied),
                ];
            })
            ->filter(function ($s){ return $s['free'] > 0; })
            ->values()
            ->toArray();
    }

    public function workerInfo($worker): array
    {
        $square = $worker->square;
        $inQueue = false;
        $squareName = null;
        if(!empty($square)){
            $squareName = $square->name ?: $square->code;

            $queue = Queue::query()->where('square_id', $square->id)->where('worker_id', $worker->id)->where('is_closed', false)->orderBy('id', 'desc')->first();
            if($queue){
                $inQueue = true;
            }
            $departmentId = $square->department_id;
        }
        if(empty($departmentId)){
            $departmentId = $worker->department_id ?: 0;
        }

        $arResult = array_merge($worker->toArray(), ['squares' => $this->getSquares($departmentId), 'inQueue' => $inQueue]);
        if(!empty($squareName)){
            $arResult = array_merge($arResult, ['square' => $squareName]);
        }

        return $arResult;
    }

}
