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
            'name' => 'error_photo',
            'type' => 'model_function',
            'label' => 'Фото',
            'function_name' => 'errorPhotoHtml',
        ]);
    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->setValidationFromArray([
            'worker' => 'required|string',
            'square' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),
            'error_note' => 'required_if:error_type,other|string',
            'error_photo' => 'required_if:error_type,no_barcode|image',
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
            'hint' => 'Заполняется, если выбрана «Другая причина»',
        ]);
        $this->crud->addField([
            'name' => 'error_photo',
            'type' => 'upload',
            'label' => 'Фото (Нет ШК)',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Загружается, если выбрано «Нет ШК»',
        ]);

        // Скрипт условного показа полей «фото» / «текст» в зависимости от выбранной ошибки
        // (инлайн через custom_html — в Backpack 6.8 метода CrudPanel::addScript нет)
        $this->crud->addField([
            'name' => 'report_form_toggle',
            'type' => 'custom_html',
            'value' => <<<'HTML'
<script>
(function () {
    function setup() {
        var select = document.getElementById('error_type');
        if (!select) { return; }
        var note = document.getElementById('error_note');
        var photo = document.getElementById('error_photo');
        var noteGroup = note ? note.closest('.form-group') : null;
        var photoGroup = photo ? photo.closest('.form-group') : null;
        function toggle() {
            var v = select.value;
            if (noteGroup) { noteGroup.style.display = (v === 'other') ? '' : 'none'; }
            if (photoGroup) { photoGroup.style.display = (v === 'no_barcode') ? '' : 'none'; }
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
