<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Square;
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
class SquaresCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    public function setup()
    {
        CRUD::setModel(Square::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/squares');
        CRUD::setEntityNameStrings('квадрат', 'Квадраты');
    }

    protected function setupListOperation()
    {

        $backpackUser = backpack_user();
        if(!empty($backpackUser)){
            if(!$backpackUser->is_root){
                if(backpack_user()->departments){
                    $arIds = [];
                    foreach (backpack_user()->departments as $department){
                        $arIds[] = $department->id;
                    }
                    $this->crud->addClause('whereIn', 'department_id', $arIds);
                }
            }
        }

        $this->crud->column('id')->type('number')->label('#');
        $this->crud->addColumn([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Номер/Название',
        ]);
        $this->crud->addColumn([
            'name' => 'code',
            'type' => 'text',
            'label' => 'Код',
        ]);
        $this->crud->addColumn([
            'name' => 'max_workers',
            'type' => 'text',
            'label' => 'Вместимость',
        ]);
        $this->crud->addColumn([
            'name' => 'workers_count',
            'type' => 'count',
            'label' => 'Сотрудников сейчас',
            'entity' => 'workers',
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

        $this->crud->orderBy('department_id', 'asc')->orderBy('code', 'asc');
    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->field('name')->type('text')->label('Номер/Название')->attributes(['required' => 'true']);
        $this->crud->field('code')->type('text')->attributes(['required'=>'true'])->label('Код');
        $this->crud->addField([
            'name' => 'max_workers',
            'type' => 'number',
            'label' => 'Вместимость (макс. сотрудников)',
            'default' => 10,
            'attributes' => ['min' => 1],
        ]);
        $this->crud->addField([
            'name' => 'department_id',
            'label' => 'Направление',
            'type' => 'select',
            'entity' => 'department',
            'attribute' => 'name',
            'model' => Department::class,
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
