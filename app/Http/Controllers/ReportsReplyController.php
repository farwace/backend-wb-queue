<?php

namespace App\Http\Controllers;

use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ReportsReplyController extends Controller{
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
