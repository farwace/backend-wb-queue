<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
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
class TablesCrudController extends CrudController
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
        CRUD::setModel(Table::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/tables');
        CRUD::setEntityNameStrings('столик', 'Столики');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->column('id')->type('number')->label('#');
        $this->crud->addColumn([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Название',
        ]);
        $this->crud->addColumn([
            'name' => 'code',
            'type' => 'text',
            'label' => 'Код',
        ]);
        $this->crud->addColumn([
            'name' => 'worker.name',
            'type' => 'text',
            'label' => 'Занят',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('worker', function ($query) use ($column, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
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

    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->field('name')->type('text')->label('Название')->attributes(['required' => 'true']);
        $this->crud->field('code')->type('text')->attributes(['required'=>'true'])->label('Код');
        $this->crud->addField([
            'name' => 'department_id',
            'label' => 'Направление',
            'type' => 'select',
            'entity' => 'department',
            'attribute' => 'name',
            'model' => Department::class,
        ]);
        $this->crud->addField([
            'name' => 'worker_id',
            'label' => 'Сотрудник',
            'type' => 'select',
            'entity' => 'worker',
            'attribute' => 'name',
            'model' => Worker::class,
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
