<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use CrudTrait;
    protected $fillable = [
        'name',
        'description',
        'amount',
        'due_date',
        'is_recurring',
        'recurring_interval',
        'paid',
        'payment_method',
        'is_installment',
        'installment_count',
        'installment_current',
        'category_bill_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(CategoryBill::class, 'category_bill_id');
    }
}
