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
            'name' => 'required|string',
            'square' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),
            'error_note' => 'required_if:error_type,other|string',
            // При ошибке «Нет ШК» обязательны: фото, соседний ШК и БК
            'error_photo' => 'required_if:error_type,no_barcode|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'neighbor_barcode' => 'required_if:error_type,no_barcode|nullable|string',
            'bk' => 'required_if:error_type,no_barcode|nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'worker' => '«ID сотрудника»',
            'name' => '«Имя»',
            'square' => '«Квадрат сотрудника»',
            'error_type' => '«Ошибка»',
            'error_note' => '«Другая причина»',
            'neighbor_barcode' => '«Соседний ШК с этого поступления»',
            'bk' => '«БК»',
            'error_photo' => '«Фото»',
        ];
    }
}
