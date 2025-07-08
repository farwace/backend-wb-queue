<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Report;
use App\Models\Table;
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
        $backpackUser = backpack_user();
        if(!empty($backpackUser)){
            if(!$backpackUser->is_root){

            }
        }

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
            'name' => 'barcode',
            'type' => 'text',
            'label' => 'ШК',
        ]);
        $this->crud->addColumn([
            'name' => 'type',
            'type' => 'text',
            'label' => 'Тип',
        ]);

        $this->crud->addColumn([
            'name' => 'shortage',
            'type' => 'text',
            'label' => 'Недостача',
        ]);
        $this->crud->addColumn([
            'name' => 'surplus',
            'type' => 'text',
            'label' => 'Излишек',
        ]);
        $this->crud->addColumn([
            'name' => 'through',
            'type' => 'text',
            'label' => 'Через "ДА"',
        ]);

        $this->crud->addColumn([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника»',
        ]);
        $this->crud->addColumn([
            'name' => 'table',
            'type' => 'text',
            'label' => '№ Стола Приемки',
        ]);
        $this->crud->addColumn([
            'name' => 'reason',
            'type' => 'text',
            'label' => 'Причина обезлички',
        ]);
        $this->crud->addColumn([
            'name' => 'count',
            'type' => 'text',
            'label' => 'Количество',
        ]);
        $this->crud->addColumn([
            'name' => 'videos',
            'type' => 'view',
            'label' => 'Обезличка видео',
            'view' => 'admin/columns/report-video'
        ]);

    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->addField([
            'name' => 'barcode',
            'type' => 'text',
            'label' => 'ШК',
        ]);
        $this->crud->addField([
            'name' => 'type',
            'type' => 'text',
            'label' => 'Тип',
        ]);
        $this->crud->addField([
            'name' => 'shortage',
            'type' => 'text',
            'label' => 'Недостача',
        ]);
        $this->crud->addField([
            'name' => 'surplus',
            'type' => 'text',
            'label' => 'Излишек',
        ]);
        $this->crud->addField([
            'name' => 'through',
            'type' => 'text',
            'label' => 'Через "ДА"',
        ]);

        $this->crud->addField([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника»',
        ]);
        $this->crud->addField([
            'name' => 'table',
            'type' => 'text',
            'label' => '№ Стола Приемки',
        ]);
        $this->crud->addField([
            'name' => 'reason',
            'type' => 'text',
            'label' => 'Причина обезлички',
        ]);
        $this->crud->addField([
            'name' => 'count',
            'type' => 'text',
            'label' => 'Количество',
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
