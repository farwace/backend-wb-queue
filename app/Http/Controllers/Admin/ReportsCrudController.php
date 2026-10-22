<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Report;
use App\Models\Worker;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class ReportsCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Report::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/reports');
        CRUD::setEntityNameStrings('отписку', 'Отписки');

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addButton('top', 'export', 'model_function', 'exportButtonContent');

        $this->crud->column('id')->type('number')->label('#ID');

        $this->crud->addColumn([
            'name' => 'department.name',
            'type' => 'text',
            'label' => 'Направление',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('department', function ($query) use ($column, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
        $this->crud->addColumn([
            'name' => 'created_at',
            'type' => 'closure',
            'label' => 'Дата/время',
            'function' => function ($entry) {
                return $entry->created_at->addHours(3)->locale('ru')
                    ->isoFormat('D MMM YYYY, HH:mm');
            }
        ]);

        $this->crud->addColumn([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника',
        ]);
        $this->crud->addColumn([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Имя',
        ]);
        $this->crud->addColumn([
            'name' => 'square',
            'type' => 'text',
            'label' => 'Квадрат сотрудника',
        ]);
        $this->crud->addColumn([
            'name' => 'error_type',
            'type' => 'closure',
            'label' => 'Ошибка',
            'function' => function ($entry) {
                return e($entry->errorTypeLabel());
            }
        ]);
        $this->crud->addColumn([
            'name' => 'error_note',
            'type' => 'text',
            'label' => 'Другая причина (текст)',
        ]);
        $this->crud->addColumn([
            'name' => 'product_barcode',
            'type' => 'text',
            'label' => 'ID (ШК) товара',
        ]);
        $this->crud->addColumn([
            'name' => 'neighbor_barcode',
            'type' => 'text',
            'label' => 'Соседний ID (ШК) с поступления',
        ]);
        $this->crud->addColumn([
            'name' => 'ttn_type',
            'type' => 'closure',
            'label' => 'ТТН',
            'function' => function ($entry) {
                return e($entry->ttnTypeLabel());
            }
        ]);
        $this->crud->addColumn([
            'name' => 'ttn_number',
            'type' => 'text',
            'label' => 'Номер ТТН',
        ]);
        $this->crud->addColumn([
            'name' => 'product_photo',
            'type' => 'closure',
            'label' => 'Фото товара',
            'escaped' => false,
            'function' => fn ($entry) => $entry->productPhotoHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'office_id_photo',
            'type' => 'closure',
            'label' => 'Фото ошибки с ТСД',
            'escaped' => false,
            'function' => fn ($entry) => $entry->officeIdPhotoHtml(),
        ]);
    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->setValidationFromArray([
            'worker' => 'required|string',
            'name' => 'required|string',
            'square' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),
            'error_note' => 'required_if:error_type,other|string',
            'product_barcode' => 'required_if:error_type,not_in_order,office_error|nullable|string',
            'neighbor_barcode' => 'required_if:error_type,no_barcode|nullable|string',
            'ttn_type' => 'required_if:error_type,not_in_order,no_barcode|nullable|in:' . implode(',', array_keys(Report::TTN_TYPES)),
            'ttn_number' => 'required_if:error_type,not_in_order,no_barcode|nullable|regex:/^\d+$/',
            'product_photo' => 'required_if:error_type,not_in_order,office_error,no_barcode|nullable|image',
            'office_id_photo' => 'required_if:error_type,office_error|nullable|image',
        ]);

        $this->crud->addField([
            'name' => 'department_id',
            'type' => 'select',
            'label' => 'Направление',
            'entity' => 'department',
            'attribute' => 'name',
            'model' => Department::class,
            'placeholder' => '— выберите направление —',
        ]);
        $this->crud->addField([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника',
        ]);
        $this->crud->addField([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Имя',
        ]);
        $this->crud->addField([
            'name' => 'square',
            'type' => 'text',
            'label' => 'Квадрат сотрудника',
        ]);
        $this->crud->addField([
            'name' => 'error_type',
            'type' => 'select_from_array',
            'label' => 'Ошибка',
            'options' => Report::ERROR_TYPES,
            'placeholder' => '— выберите ошибку —',
        ]);
        $this->crud->addField([
            'name' => 'error_note',
            'type' => 'text',
            'label' => 'Другая причина (текст)',
            'hint' => 'Для «Другая причина»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode',
            'type' => 'text',
            'label' => 'ID (ШК) товара',
            'hint' => 'Для «Товар не в заказе» / «Неверный офис»',
        ]);
        $this->crud->addField([
            'name' => 'neighbor_barcode',
            'type' => 'text',
            'label' => 'Соседний ID (ШК) с поступления',
            'hint' => 'Для «Без ID (ШК)»',
        ]);
        $this->crud->addField([
            'name' => 'ttn_type',
            'type' => 'select_from_array',
            'label' => 'ТТН',
            'options' => Report::TTN_TYPES,
            'placeholder' => '— выберите ТТН —',
            'hint' => 'Для «Товар не в заказе» / «Без ID (ШК)»',
        ]);
        $this->crud->addField([
            'name' => 'ttn_number',
            'type' => 'text',
            'label' => 'Номер ТТН',
            'hint' => 'Только цифры',
        ]);
        $this->crud->addField([
            'name' => 'product_photo',
            'type' => 'upload',
            'label' => 'Фото товара',
            'disk' => 's3',
            'prefix' => 'reports-photos',
        ]);
        $this->crud->addField([
            'name' => 'office_id_photo',
            'type' => 'upload',
            'label' => 'Фото ошибки с ТСД',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Для «Неверный офис»',
        ]);

        // Условный показ полей в зависимости от выбранного типа ошибки
        // (инлайн через custom_html — в Backpack 6.8 метода CrudPanel::addScript нет)
        $this->crud->addField([
            'name' => 'report_form_toggle',
            'type' => 'custom_html',
            'value' => <<<'HTML'
<script>
(function () {
    // элемент => список типов ошибки, при которых поле показывается
    var rules = {
        error_note: ['other'],
        product_barcode: ['not_in_order', 'office_error'],
        neighbor_barcode: ['no_barcode'],
        ttn_type: ['not_in_order', 'no_barcode'],
        ttn_number: ['not_in_order', 'no_barcode'],
        product_photo: ['not_in_order', 'office_error', 'no_barcode'],
        office_id_photo: ['office_error']
    };
    function setup() {
        var select = document.getElementById('error_type');
        if (!select) { return; }
        var groups = {};
        Object.keys(rules).forEach(function (key) {
            var el = document.getElementById(key);
            groups[key] = el ? el.closest('.form-group') : null;
        });
        function toggle() {
            var v = select.value;
            Object.keys(rules).forEach(function (key) {
                if (groups[key]) {
                    groups[key].style.display = rules[key].indexOf(v) !== -1 ? '' : 'none';
                }
            });
        }
        select.addEventListener('change', toggle);
        toggle();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else { setup(); }
})();
</script>
HTML,
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
