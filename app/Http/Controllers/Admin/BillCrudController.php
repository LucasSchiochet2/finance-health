<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BillRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class BillCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BillCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Bill::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/bill');
        CRUD::setEntityNameStrings('bill', 'bills');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
   protected function setupListOperation()
{
    CRUD::column('name');
    CRUD::column('amount');
    CRUD::column('due_date');

    CRUD::column('category_bill_id')
        ->type('select')
        ->label('Categoria')
        ->entity('category')
        ->model(\App\Models\CategoryBill::class)
        ->attribute('name');

    CRUD::column('paid')->type('boolean');
}
    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */

        protected function setupCreateOperation()
{
    CRUD::setValidation(BillRequest::class);

    CRUD::field('category_bill_id')
        ->type('select')
        ->label('Categoria')
        ->entity('category')
        ->model(\App\Models\CategoryBill::class)
        ->attribute('name');

    CRUD::field('name')->label('Nome da Conta');
    CRUD::field('description')->label('Descrição');
    CRUD::field('amount')->type('number')->label('Valor');
    CRUD::field('due_date')->type('date')->label('Data de Vencimento');

    CRUD::field('is_installment')->type('checkbox')->label('É parcelado?');
    CRUD::field('installment_count')
        ->type('number')
        ->label('Quantidade de Parcelas');
    CRUD::field('installment_current')
        ->type('number')
        ->label('Parcela Atual')
        ->default(1)
        ->attributes(['readonly' => 'readonly']);

    CRUD::field('is_recurring')->type('checkbox')->label('É recorrente?');
    CRUD::field('recurring_interval')
        ->type('select_from_array')
        ->label('Intervalo de Recorrência')
        ->options([
            1 => 'Mensal',
            2 => 'Semanal',
            3 => 'Anual'
        ]);


    CRUD::field('payment_method')->type('select_from_array')->options([
        'credit_card' => 'Cartão de Crédito',
        'debit_card' => 'Cartão de Débito',
        'bank_transfer' => 'Transferência Bancária',
        'cash' => 'Dinheiro',
        'other' => 'Outro',
    ]);
    CRUD::field('paid')->type('checkbox');
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

    public function store()
    {
        $response = $this->traitStore();
        $entry = $this->crud->entry;

        if ($entry && $entry->is_installment && $entry->installment_count > 1) {
            $totalInstallments = (int)$entry->installment_count;
            $startDate = \Carbon\Carbon::parse($entry->due_date);

            for ($i = 2; $i <= $totalInstallments; $i++) {
                $installment = $entry->replicate();
                $installment->installment_current = $i;
                $installment->due_date = $startDate->copy()->addMonths($i - 1);
                $installment->paid = false;
                $installment->save();
            }
        }

        return $response;
    }
}
