<?php

use App\Http\Controllers\Workers\ApiController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1.0')->group(function (){
    //Для сотрудников
    Route::post('/auth', [ApiController::class, 'auth']);
    Route::post('/update', [ApiController::class, 'update']);
    Route::post('/select-table', [ApiController::class, 'selectTable']);
    Route::post('/enter-queue', [ApiController::class, 'enterQueue']);
    Route::post('/receive-item', [ApiController::class, 'receiveItem']);
    Route::post('/leave-table', [ApiController::class, 'leaveTable']);

    //Для грузчиков
    Route::get('/queue/{direction}', [ApiController::class, 'getQueue']);
    Route::get('/department-tables-length/{direction}', [ApiController::class, 'getDepartmentTablesLength']); //Ограничение сколько столов показывать на странице грузчиков

    //Для админов
    Route::get('/unavailable-tables/{direction}', [ApiController::class, 'getUnavailableTables']);
    Route::post('/check-table', [ApiController::class, 'setTableChecked']);


    /** @deprecated  */
    Route::get('/department-list', [ApiController::class, 'getDepartmentList']);
});
