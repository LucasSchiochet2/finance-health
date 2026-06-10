<?php

namespace App\Http\Requests;

use App\Models\DietMeal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DietMealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'meal_type' => ['required', Rule::in(DietMeal::MEAL_TYPES)],
            'status' => ['required', Rule::in(DietMeal::STATUSES)],
            'observation' => 'nullable|string',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
