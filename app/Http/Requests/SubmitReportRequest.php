<?php


namespace App\Http\Requests;


use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'worker' => 'required|string',
            'square' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),
            'error_note' => 'required_if:error_type,other|string',
            'error_photo' => 'required_if:error_type,no_barcode|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ];
    }

    public function attributes(): array
    {
        return [
            'worker' => '«ID сотрудника»',
            'square' => '«Квадрат сотрудника»',
            'error_type' => '«Ошибка»',
            'error_note' => '«Другая причина»',
            'error_photo' => '«Фото»',
        ];
    }
}
