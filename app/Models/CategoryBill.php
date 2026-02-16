<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class CategoryBill extends Model
{
    use CrudTrait;
    protected $fillable = [
        'name',
        'icon',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
