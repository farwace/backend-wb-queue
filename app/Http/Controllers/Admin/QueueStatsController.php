<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueStatsController extends Controller
{
    public function index(Request $request)
    {
        // Список сотрудников для фильтра
        $workers = QueueLog::select('worker_badge', 'worker_name')
        ->distinct()
        ->orderBy('worker_name')
        ->get();
        $selectedBadge = $request->get('worker_badge');

        // 1. Количество обработанных товаров всеми сотрудниками по дням
        $allByDay = QueueLog::where('status', 'success')
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // 2. Количество обработанных товаров выбранным сотрудником по дням
        $byWorkerByDay = collect();
        if ($selectedBadge) {
        $byWorkerByDay = QueueLog::where('status', 'success')
            ->where('worker_badge', $selectedBadge)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        }

        // 3. Сравнение количества товаров по направлениям (департаментам)
        $byDept = QueueLog::where('status', 'success')
        ->select('department_id', DB::raw('count(*) as total'))
        ->groupBy('department_id')
        ->with('department')
        ->get();

        // 4. Количество входов/выходов по дням выбранного сотрудника
        $loginLogout = collect();
        if ($selectedBadge) {
        $loginLogout = QueueLog::whereIn('status', ['login', 'logout'])
            ->where('worker_badge', $selectedBadge)
                ->select(DB::raw('DATE(created_at) as date'), 'status', DB::raw('count(*) as total'))
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();
        }

        // 5. Количество попыток раньше времени (warning) по дням
        $warnings = QueueLog::where('status', 'warning')
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Подготовка массивов для JS
        $dates = $allByDay->pluck('date');
        $allTotals = $allByDay->pluck('total');
        $workerDates = $byWorkerByDay->pluck('date');
        $workerTotals = $byWorkerByDay->pluck('total');
        $deptLabels = $byDept->map(fn($d) => $d->department->name ?? '—');
        $deptTotals = $byDept->pluck('total');
        $warningDates = $warnings->pluck('date');
        $warningTotals = $warnings->pluck('total');

        return view('admin.pages.queue_stats', compact(
            'workers', 'selectedBadge',
            'dates', 'allTotals',
            'workerDates', 'workerTotals',
            'deptLabels', 'deptTotals',
            'loginLogout',
            'warningDates', 'warningTotals'
        ));
    }
}

