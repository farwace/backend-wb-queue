<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class SubmitReceiveItemRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => 'required|string',
            'attachments' => 'sometimes | array',
            'attachments.*' => 'required | file',
            'message' => 'required|string',
            'is_admin' => 'sometimes'
        ];
    }

    public function attributes(): array
    {
        return [
            'direction' => '«Направление»',
            'attachments' => '«Вложения»',
            'message' => '«Номер короба»'
        ];
    }
}
