<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class SubmitLeaveTableRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => 'sometimes|string',
            'attachments' => 'sometimes | array',
            'attachments.*' => 'required | file',
            'is_admin' => 'sometimes'
        ];
    }

    public function attributes(): array
    {
        return [
            'direction' => '«Направление»',
            'attachments' => '«Вложения»',
        ];
    }
}
