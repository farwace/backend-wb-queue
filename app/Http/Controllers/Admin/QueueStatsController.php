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

        // 1. Обработано товаров всеми сотрудниками по дням
        $allByDay = QueueLog::where('status', 'success')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Обработано товаров выбранным сотрудником по дням
        $byWorkerByDay = collect();
        if ($selectedBadge) {
            $byWorkerByDay = QueueLog::where('status', 'success')
                ->where('worker_badge', $selectedBadge)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // 3a. Количество товаров по направлениям (департаментам) по дням
        $deptByDay = QueueLog::where('status', 'success')
            ->select(DB::raw('DATE(created_at) as date'), 'department_id', DB::raw('count(*) as total'))
            ->groupBy('date', 'department_id')
            ->orderBy('date')
            ->with('department')
            ->get();

        $deptDates = $deptByDay->pluck('date')->unique()->sort()->values();
        $departments = $deptByDay
            ->map(fn($d) => ['id' => $d->department_id, 'name' => $d->department->name ?? '—'])
            ->unique('id')
            ->values();

        $deptDataSets = [];
        foreach ($departments as $dept) {
            $data = $deptDates->map(fn($date) =>
                ($deptByDay->first(fn($d) => $d->department_id === $dept['id'] && $d->date === $date)->total) ?? 0
            );
            $deptDataSets[] = [
                'label' => $dept['name'],
                'data'  => $data,
            ];
        }

        // 4. Входы/выходы по дням выбранного сотрудника
        $loginLogout = collect();
        if ($selectedBadge) {
            $loginLogout = QueueLog::whereIn('status', ['login', 'logout'])
                ->where('worker_badge', $selectedBadge)
                ->select(DB::raw('DATE(created_at) as date'), 'status', DB::raw('count(*) as total'))
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get();
        }

        // 5. Попытки раньше времени по дням
        $warnings = QueueLog::where('status', 'warning')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Подготовка массивов для JS
        return view('admin.pages.queue_stats', compact(
            'workers', 'selectedBadge',
            'allByDay', 'byWorkerByDay',
            'deptDates', 'deptDataSets',
            'loginLogout', 'warnings'
        ));
    }
}
