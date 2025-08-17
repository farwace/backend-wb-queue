<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Table;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        /** @var Admin $admin */
        $admin = backpack_user();
        $arIds = [];
        /** @var Department $dep */
        foreach ($admin->departments as $dep) {
            $arIds[] = $dep->id;
        }

        $departmentStats = [];

        if(count($arIds) > 0 || backpack_user()->is_root) {
            $departmentsQuery = Department::query();
            if(count($arIds) > 0){
                $departmentsQuery->whereIn('id', $arIds);
            }
            $departments = $departmentsQuery->orderBy('name')->get();

            // Get statistics for all departments
            foreach ($departments as $department) {
                $departmentStats[] = [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'processed_pallets_today' => $this->getProcessedPalletsCount($department->id, 'today'),
                    'processed_pallets_week' => $this->getProcessedPalletsCount($department->id, 'week'),
                    'processed_pallets_month' => $this->getProcessedPalletsCount($department->id, 'month'),
                    'tables_in_queue' => $this->getTablesInQueue($department->id),
                    'waiting_queue_time' => $this->getWaitingQueueTime($department->id),
                    'workers_online' => $this->getWorkersOnline($department->id),
                ];
            }
        }


        return view('admin.statistics.index', compact('departments', 'departmentStats'));
    }

    public function getDepartmentChartData(Request $request, $departmentId)
    {
        $period = $request->get('period', 'days'); // days, months, hours
        $department = Department::findOrFail($departmentId);

        $data = [];

        switch ($period) {
            case 'hours':
                $data = $this->getHourlyData($departmentId);
                break;
            case 'days':
                $data = $this->getDailyData($departmentId);
                break;
            case 'months':
                $data = $this->getMonthlyData($departmentId);
                break;
        }

        return response()->json([
            'department' => $department->name,
            'period' => $period,
            'data' => $data
        ]);
    }

    public function getWorkerStats(Request $request)
    {
        $workerCode = $request->get('worker_code');

        if (empty($workerCode)) {
            return response()->json(['error' => 'Укажите номер бейджа сотрудника'], 400);
        }

        $worker = Worker::where('code', $workerCode)->first();

        if (!$worker) {
            return response()->json(['error' => 'Сотрудник не найден'], 404);
        }

        // Get daily statistics for the last 30 days
        $dailyStats = QueueLog::where('worker_badge', $workerCode)
            ->where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as pallets_count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'worker' => [
                'code' => $worker->code,
                'name' => $worker->name,
                'department' => $worker->department ? $worker->department->name : 'Не указано'
            ],
            'daily_stats' => $dailyStats
        ]);
    }

    private function getProcessedPalletsCount($departmentId, $period)
    {
        $query = QueueLog::where('department_id', $departmentId)
            ->where('status', 'success');

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->where('created_at', '>=', Carbon::now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', Carbon::now()->startOfMonth());
                break;
        }

        return $query->count();
    }

    private function getWorkersOnline($departmentId)
    {
        return Table::whereHas('worker')->where('department_id', $departmentId)->count();
    }

    private function getTablesInQueue($departmentId)
    {
        return Queue::whereHas('table', function($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })
        ->where('is_closed', false)
        ->count();
    }

    private function getWaitingQueueTime($departmentId)
    {
        // Получить среднее время ожидания палета за текущую смену с 8 утра до 8 вечера или с 8 вечера до 8 утра сегодня по МСК
        // время ожидание палета - это разница между created_at и updated_at в записи, для которой is_closed = true

        // Устанавливаем московскую временную зону
        $moscowNow = Carbon::now('Europe/Moscow');
        $currentHour = $moscowNow->hour;

        // Определяем временные рамки текущей смены
        if ($currentHour >= 8 && $currentHour < 20) {
            // Дневная смена: с 8:00 до 20:00 сегодня
            $shiftStart = $moscowNow->copy()->startOfDay()->addHours(8);
            $shiftEnd = $moscowNow->copy()->startOfDay()->addHours(20);
        } else {
            // Ночная смена: с 20:00 вчера до 8:00 сегодня или с 20:00 сегодня до 8:00 завтра
            if ($currentHour < 8) {
                // Сейчас утро до 8:00 - смена началась вчера в 20:00
                $shiftStart = $moscowNow->copy()->subDay()->startOfDay()->addHours(20);
                $shiftEnd = $moscowNow->copy()->startOfDay()->addHours(8);
            } else {
                // Сейчас вечер после 20:00 - смена началась сегодня в 20:00
                $shiftStart = $moscowNow->copy()->startOfDay()->addHours(20);
                $shiftEnd = $moscowNow->copy()->addDay()->startOfDay()->addHours(8);
            }
        }

        // Получаем закрытые записи очереди за текущую смену
        $closedQueues = Queue::whereHas('table', function($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })
        ->where('is_closed', true)
        ->where('created_at', '>=', $shiftStart)
        ->where('created_at', '<=', $shiftEnd)
        ->whereNotNull('updated_at')
        ->get();

        if ($closedQueues->isEmpty()) {
            return '-';
        }

        // Вычисляем среднее время ожидания в минутах
        $totalWaitingTimeMinutes = 0;
        $count = 0;

        foreach ($closedQueues as $queue) {
            $waitingTime = Carbon::parse($queue->updated_at)->diffInMinutes(Carbon::parse($queue->created_at));
            $totalWaitingTimeMinutes += $waitingTime;
            $count++;
        }

        $averageWaitingTime = $totalWaitingTimeMinutes / $count;

        return round($averageWaitingTime, 1);
    }

    private function getHourlyData($departmentId)
    {
        return QueueLog::where('department_id', $departmentId)
            ->where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->select(
                DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
                DB::raw('COUNT(*) as pallets_count')
            )
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('hour')
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->hour . ':00',
                    'value' => $item->pallets_count
                ];
            });
    }

    private function getDailyData($departmentId)
    {
        return QueueLog::where('department_id', $departmentId)
            ->where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as pallets_count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'label' => Carbon::parse($item->date)->format('d.m'),
                    'value' => $item->pallets_count
                ];
            });
    }

    private function getMonthlyData($departmentId)
    {
        return QueueLog::where('department_id', $departmentId)
            ->where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                DB::raw('COUNT(*) as pallets_count')
            )
            ->groupBy(DB::raw('EXTRACT(YEAR FROM created_at)'), DB::raw('EXTRACT(MONTH FROM created_at)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                return [
                    'label' => Carbon::createFromDate($item->year, $item->month, 1)->format('m.Y'),
                    'value' => $item->pallets_count
                ];
            });
    }
}
