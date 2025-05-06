<?php

namespace App\Http\Controllers\Admin;

use App\Models\Queue;
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
class QueueCrudController extends CrudController
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
        CRUD::setModel(Queue::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/queue');
        CRUD::setEntityNameStrings('очередь', 'Очереди');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addClause('where', 'is_closed', false);
        $this->crud->removeButton('create');
        $this->crud->removeButton('delete');
        $this->crud->column('id')->type('number')->label('#');
        $this->crud->addColumn([
            'name' => 'table.name',
            'label' => 'Стол',
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('table', function ($query) use ($column, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
        $this->crud->addColumn([
            'name' => 'table.department.name',
            'label' => 'Направление',
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('table', function ($query) use ($column, $searchTerm) {
                    $query->whereHas('department', function ($query) use ($column, $searchTerm) {
                        $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                    });
                });
            }
        ]);

        $this->crud->addColumn([
            'name' => 'worker.name',
            'label' => 'Сотрудник',
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('worker', function ($query) use ($column, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
        $this->crud->addColumn([
            'name' => 'worker.code',
            'label' => 'Идентификатор сотрудника',
            'type' => 'text',
        ]);
        $this->crud->addColumn([
            'name' => 'is_closed',

        ]);

        $this->crud->addColumn([
            'name' => 'created_at',
            'label' => 'Дата',
            'type' => 'model_function',
            'function_name' => 'getCreatedAtForBackpack',
        ]);

    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){

    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->crud->addField([
            'name' => 'is_closed',
            'type' => 'boolean',
            'label' => 'Товар принят'
        ]);
    }
}
