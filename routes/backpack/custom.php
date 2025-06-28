<?php

use App\Http\Exports\LogsExport;
use App\Http\Exports\LogsExportMonth;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('workers', 'WorkersCrudController');
    Route::crud('departments', 'DepartmentsCrudController');
    Route::crud('tables', 'TablesCrudController');
    Route::crud('queue', 'QueueCrudController');
    Route::crud('loaders-settings', 'LoadersSettingsCrudController');
    Route::crud('reports', 'ReportsCrudController');
    Route::crud('replies', 'RepliesCrudController');
    Route::crud('admins', 'AdminsCrudController');
    Route::get('/export-logs/{departmentId}', function ($departmentId, Request $request) {
        $logsExport = new LogsExport();
        return $logsExport->execute('department-' . $departmentId, ['departmentId' => $departmentId]);
    })->name('admin.export-logs');
    Route::get('/export-logs-month/{departmentId}', function ($departmentId, Request $request) {
        $logsExport = new LogsExportMonth();
        return $logsExport->execute('department-' . $departmentId . '-month', ['departmentId' => $departmentId]);
    })->name('admin.export-logs-month');

    Route::get('queue-stats', 'QueueStatsController@index')->name('queue-stats.index');

    Route::get('/departments-list', function (Request $request){
        $backpackUser = backpack_user();
        if(!empty($backpackUser->id)){
            if($backpackUser->is_root){
                return response()->json(Department::query()->orderBy('code', 'asc')->get());
            }
            $arIds = [];
            foreach ($backpackUser->departments as $dep){
                $arIds[] = $dep->id;
            }
            if(count($arIds) > 0){
                return response()->json(Department::query()->whereIn('id', $arIds)->orderBy('code', 'asc')->get());
            }
        }
        return response()->json([]);

    })->name('admin.departmentsList');

}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
