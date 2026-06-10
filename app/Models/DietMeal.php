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

    public const TYPE_CAFE_DA_MANHA = 'cafe_da_manha';
    public const TYPE_ALMOCO = 'almoco';
    public const TYPE_LANCHE_DA_TARDE = 'lanche_da_tarde';
    public const TYPE_JANTA = 'janta';
    public const TYPE_CEIA = 'ceia';
    public const TYPE_EXTRA = 'extra';

    public const STATUSES = [
        self::STATUS_PERFEITO,
        self::STATUS_BOM,
        self::STATUS_MEDIO,
        self::STATUS_FORA,
    ];

    public const MEAL_TYPES = [
        self::TYPE_CAFE_DA_MANHA,
        self::TYPE_ALMOCO,
        self::TYPE_LANCHE_DA_TARDE,
        self::TYPE_JANTA,
        self::TYPE_CEIA,
        self::TYPE_EXTRA,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PERFEITO => 'Perfeito',
        self::STATUS_BOM => 'Bom',
        self::STATUS_MEDIO => 'Medio',
        self::STATUS_FORA => 'Fora',
    ];

    public const MEAL_TYPE_LABELS = [
        self::TYPE_CAFE_DA_MANHA => 'Cafe da manha',
        self::TYPE_ALMOCO => 'Almoco',
        self::TYPE_LANCHE_DA_TARDE => 'Lanche da tarde',
        self::TYPE_JANTA => 'Janta',
        self::TYPE_CEIA => 'Ceia',
        self::TYPE_EXTRA => 'Extra',
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
        'meal_type',
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

    public static function mealTypeOptions(): array
    {
        return self::MEAL_TYPE_LABELS;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function mealTypeLabel(string $mealType): string
    {
        return self::MEAL_TYPE_LABELS[$mealType] ?? $mealType;
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
