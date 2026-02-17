<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use CrudTrait;

    protected $table = 'credit_cards';
    protected $fillable = ['user_id', 'name', 'closing_day', 'expiration_date', 'limit'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'credit_card_id');
    }
}
