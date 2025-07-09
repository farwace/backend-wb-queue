<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('name')->get();

        // Get statistics for all departments
        $departmentStats = [];
        foreach ($departments as $department) {
            $departmentStats[] = [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'processed_pallets_today' => $this->getProcessedPalletsCount($department->id, 'today'),
                'processed_pallets_week' => $this->getProcessedPalletsCount($department->id, 'week'),
                'processed_pallets_month' => $this->getProcessedPalletsCount($department->id, 'month'),
                'tables_in_queue' => $this->getTablesInQueue($department->id),
            ];
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

    private function getTablesInQueue($departmentId)
    {
        return Queue::whereHas('table', function($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })
        ->where('is_closed', false)
        ->count();
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
