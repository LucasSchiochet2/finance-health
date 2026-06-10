<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use CrudTrait;

    public const TYPE_ENTRADA = 'entrada';
    public const TYPE_SAIDA = 'saida';
    public const TYPES = [
        self::TYPE_ENTRADA,
        self::TYPE_SAIDA,
    ];

    protected $fillable = [
        'user_id',
        'bill_id',
        'type',
        'amount',
        'date',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
