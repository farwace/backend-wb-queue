<?php

namespace App\Http\Controllers\Reports;

use App\Events\OrderRequested;
use App\Http\Controllers\Controller;
use App\Http\Resources\QueueResource;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\LoadersSettings;
use App\Models\Queue;
use App\Models\QueueLog;
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


    public function tryAuth(Request $request): JsonResponse
    {
        $arRequest = $request->toArray();

        $password = !empty($arRequest['password']) ? $arRequest['password'] : '-';
        $departmentCode = !empty($arRequest['department']) ? $arRequest['department'] : '-';

        $department = Department::query()->where('code', $departmentCode)->where('password', $password)->first();
        if(empty($department)){
            return $this->failure('Пароль неверный!');
        }
        return $this->success('OK', 'Success');

    }

}
