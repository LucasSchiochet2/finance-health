<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class DietMeal extends Model
{
    use CrudTrait;

    public const STATUS_PERFEITO = 'perfeito';
    public const STATUS_BOM = 'bom';
    public const STATUS_MEDIO = 'medio';
    public const STATUS_FORA = 'fora';

    public const STATUSES = [
        self::STATUS_PERFEITO,
        self::STATUS_BOM,
        self::STATUS_MEDIO,
        self::STATUS_FORA,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PERFEITO => 'Perfeito',
        self::STATUS_BOM => 'Bom',
        self::STATUS_MEDIO => 'Medio',
        self::STATUS_FORA => 'Fora',
    ];

    public const STATUS_SCORES = [
        self::STATUS_PERFEITO => 100,
        self::STATUS_BOM => 75,
        self::STATUS_MEDIO => 50,
        self::STATUS_FORA => 0,
    ];

    protected $fillable = [
        'user_id',
        'date',
        'status',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public static function statusOptions(): array
    {
        return self::STATUS_LABELS;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function statusScore(?string $status): int
    {
        return self::STATUS_SCORES[$status] ?? 0;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
