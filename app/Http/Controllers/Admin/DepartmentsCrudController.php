<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
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
class DepartmentsCrudController extends CrudController
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
        CRUD::setModel(Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/departments');
        CRUD::setEntityNameStrings('направления', 'Направление');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->removeButton('delete');

        $backpackUser = backpack_user();
        if(!empty($backpackUser)){
            if(!($backpackUser->is_root)){
                $this->crud->removeButton('create');

                if($backpackUser->departments){
                    $arIds = [];
                    foreach ($backpackUser->departments as $department){
                        $arIds[] = $department->id;
                    }
                    $this->crud->addClause('whereIn', 'id', $arIds);
                }
            }
        }

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
            'name' => 'queue_length',
            'type' => 'text',
            'label' => 'Количество столов в очереди',
        ]);
        $this->crud->addColumn([
            'name' => 'photo_required',
            'type' => 'boolean',
            'label' => 'Отправка фото действий',
        ]);



        $this->crud->column('sort')->type('integer')->label('Сорт.');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        if(backpack_user()->is_root) {
            $this->crud->field('name')->type('text')->label('Название')->attributes(['required' => 'true']);
            $this->crud->field('code')->type('text')->attributes(['required' => 'true'])->label('Код');
            $this->crud->field('sort')->type('number')->default(500)->label('Сорт.');
            $this->crud->field('queue_length')->type('number')->default(4)->label('Количество столов в очереди');
            $this->crud->field('password')->type('text')->label('Пароль для открытия страниц');
            $this->crud->field('photo_required')->type('boolean')->label('Отправка фото действий');
        }
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        if(backpack_user()->is_root){
            $this->setupCreateOperation();
        }
        else{
            $this->crud->field('password')->type('text')->label('Пароль для открытия страниц');
        }

    }
}
